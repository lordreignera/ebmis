<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\Disbursement;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;

class CheckDisbursements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disbursements:check 
                            {--txn= : Specific transaction ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check pending mobile money disbursements and auto-process successful ones';

    protected $processedCount = 0;
    protected $completedCount = 0;
    protected $failedCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting disbursement status check...');
        
        // Get pending disbursements with mobile money transactions
        $disbursements = DB::table('disbursements')
            ->join('disbursement_txn', function($join) {
                $join->on('disbursements.id', '=', 'disbursement_txn.disbursement_id')
                     ->where('disbursement_txn.status', '=', '00');
            })
            ->where('disbursements.status', 0) // Only pending disbursements
            ->where('disbursements.payment_type', 1) // Mobile money only
            ->select(
                'disbursements.id as disbursement_id',
                'disbursements.loan_id',
                'disbursements.loan_type',
                'disbursements.amount',
                'disbursement_txn.id as payment_id',
                'disbursement_txn.txnref as trans_id'
            );

        // If specific transaction specified
        if ($this->option('txn')) {
            $disbursements->where('disbursement_txn.txnref', $this->option('txn'));
        }

        $disbursements = $disbursements->get();

        if ($disbursements->isEmpty()) {
            $this->info('No pending mobile money disbursements found.');
            return 0;
        }

        $this->info('Found ' . $disbursements->count() . ' pending disbursements.');

        foreach ($disbursements as $disbursement) {
            $this->processDisbursement($disbursement);
        }

        $this->info("Summary: Processed={$this->processedCount}, Completed={$this->completedCount}, Failed={$this->failedCount}");
        
        Log::info('Disbursement check completed', [
            'processed' => $this->processedCount,
            'completed' => $this->completedCount,
            'failed' => $this->failedCount
        ]);

        return 0;
    }

    /**
     * Process a single disbursement
     */
    protected function processDisbursement($disbursement)
    {
        if (empty($disbursement->trans_id)) {
            $this->warn("Skipping disbursement ID {$disbursement->disbursement_id} (empty trans_id)");
            return;
        }

        $this->info("Checking disbursement {$disbursement->code} (txn: {$disbursement->trans_id})...");

        // Query FlexiPay for status
        try {
            $response = Http::timeout(30)
                ->asForm()
                ->withOptions(['verify' => false]) // Disable SSL verification for local dev
                ->post('https://emuria.net/flexipay/checkFromMMStatusProd.php', [
                    'reference' => $disbursement->trans_id
                ]);

            if (!$response->successful()) {
                $this->error("HTTP error for {$disbursement->trans_id}: " . $response->status());
                return;
            }

            $data = $response->json();

            if (!isset($data['statusCode'])) {
                $this->error("Invalid response for {$disbursement->trans_id}");
                Log::error('Invalid FlexiPay response', [
                    'trans_id' => $disbursement->trans_id,
                    'response' => $response->body()
                ]);
                return;
            }

            $statusCode = (string)$data['statusCode'];
            $statusDesc = $data['statusDescription'] ?? '';

            $this->info("  Status: {$statusCode} - {$statusDesc}");

            // Update raw_payments
            DB::table('raw_payments')
                ->where('id', $disbursement->payment_id)
                ->update([
                    'status' => 'Processed',
                    'pay_status' => $statusCode,
                    'pay_message' => $statusDesc,
                    'pay_date' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

            // Update disbursement transaction if exists
            DB::table('disbursement_transactions')
                ->where('disbursement_id', $disbursement->disbursement_id)
                ->update([
                    'status' => $statusCode,
                    'updated_at' => Carbon::now(),
                ]);

            $this->processedCount++;

            // Process if disbursement is successful (statusCode = '00' or '01')
            if (in_array($statusCode, ['00', '01'])) {
                $this->processSuccessfulDisbursement($disbursement, $statusCode, $statusDesc);
            } else {
                $this->info("  Disbursement failed or still pending (status: {$statusCode})");
                
                // If status is '57' (FAILED) or other terminal status, mark as failed
                if (in_array($statusCode, ['57', '58', '59'])) {
                    $this->processFailedDisbursement($disbursement, $statusCode, $statusDesc);
                }
            }

        } catch (\Exception $e) {
            $this->error("Error processing {$disbursement->trans_id}: " . $e->getMessage());
            Log::error('Disbursement check error', [
                'trans_id' => $disbursement->trans_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful disbursement - complete it and update loan status
     */
    protected function processSuccessfulDisbursement($disbursement, $statusCode, $statusDesc)
    {
        $this->info("  Processing successful disbursement ID {$disbursement->disbursement_id}");
        $this->completedCount++;

        try {
            DB::beginTransaction();

            // Update disbursement status to disbursed (2)
            DB::table('disbursements')
                ->where('id', $disbursement->disbursement_id)
                ->update([
                    'status' => 2, // Disbursed
                    'updated_at' => Carbon::now(),
                ]);

            // Get the actual loan model
            $loan = $disbursement->loan_type == 1 
                ? PersonalLoan::find($disbursement->loan_id)
                : GroupLoan::find($disbursement->loan_id);

            if ($loan) {
                // Update loan status to active/disbursed (status 2)
                $loanTable = $disbursement->loan_type == 1 ? 'personal_loans' : 'group_loans';
                DB::table($loanTable)
                    ->where('id', $loan->id)
                    ->update(['status' => 2]);

                $this->info("  Loan {$loan->id} marked as disbursed");

                // Generate or recalculate repayment schedules
                $this->generateRepaymentSchedules($loan, $disbursement);

                // Deduct from investment account if exists
                $disbursementModel = Disbursement::find($disbursement->disbursement_id);
                if ($disbursementModel && $disbursementModel->inv_id) {
                    DB::table('investment')
                        ->where('id', $disbursementModel->inv_id)
                        ->decrement('amount', $disbursement->amount);
                    
                    $this->info("  Investment account debited: {$disbursement->amount}");
                }
            }

            // Log trail
            DB::table('trail')->insert([
                'action' => "Auto completed disbursement: disbursement_id={$disbursement->disbursement_id};txn={$disbursement->trans_id}",
                'date_created' => Carbon::now(),
                'ip_address' => '127.0.0.1',
                'userid' => 0,
                'change_vals' => "disbursement_id={$disbursement->disbursement_id};loan_id={$disbursement->loan_id};amount={$disbursement->amount}"
            ]);

            DB::commit();

            Log::info('Disbursement auto-completed', [
                'disbursement_id' => $disbursement->disbursement_id,
                'loan_id' => $disbursement->loan_id,
                'amount' => $disbursement->amount
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("  Error completing disbursement: " . $e->getMessage());
            Log::error('Error auto-completing disbursement', [
                'disbursement_id' => $disbursement->disbursement_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process failed disbursement
     */
    protected function processFailedDisbursement($disbursement, $statusCode, $statusDesc)
    {
        $this->info("  Processing failed disbursement ID {$disbursement->disbursement_id}");
        $this->failedCount++;

        // Update disbursement status to rejected/failed (3)
        DB::table('disbursements')
            ->where('id', $disbursement->disbursement_id)
            ->update([
                'status' => 3, // Failed/Rejected
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n\nAuto-marked as failed: {$statusDesc}')"),
                'updated_at' => Carbon::now(),
            ]);

        // Log trail
        DB::table('trail')->insert([
            'action' => "Auto failed disbursement: disbursement_id={$disbursement->disbursement_id};txn={$disbursement->trans_id}",
            'date_created' => Carbon::now(),
            'ip_address' => '127.0.0.1',
            'userid' => 0,
            'change_vals' => "disbursement_id={$disbursement->disbursement_id};status_code={$statusCode};message={$statusDesc}"
        ]);

        $this->info("  Disbursement marked as FAILED");
    }

    /**
     * Generate repayment schedules for disbursed loan
     */
    private function generateRepaymentSchedules($loan, $disbursement)
    {
        // Check if schedules already exist
        $existingSchedules = DB::table('loan_schedules')
            ->where('loan_id', $loan->id)
            ->count();

        if ($existingSchedules > 0) {
            $this->info("  Schedules already exist ({$existingSchedules} schedules)");
            return;
        }

        // Get disbursement date from disbursements table
        $disbursementRecord = DB::table('disbursements')
            ->where('id', $disbursement->disbursement_id)
            ->first();

        $principal = $loan->principal;
        $interest = $loan->interest / 100; // Convert to decimal
        $period = $loan->period;
        $periodType = $loan->period_type ?? 3; // Default to daily
        
        $disbursementDate = \Carbon\Carbon::parse($disbursementRecord->disbursement_date ?? $disbursementRecord->created_at);
        
        $balance = $principal;
        $schedulesCreated = 0;
        
        $principalPerPeriod = $principal / $period;

        // HALF-TERM INTEREST FORMULA (Universal for ALL loan types)
        // ALL interest is paid in FIRST HALF of loan term
        // SECOND HALF = Principal only (no interest)
        
        // Calculate half-term
        $halfTerm = floor($period / 2);
        
        // Calculate total interest based on loan type
        if ($periodType == 2) {
            // MONTHLY loans: Interest rate is DOUBLED
            $totalInterest = $principal * ($interest * 2);
        } else {
            // WEEKLY and DAILY loans: Interest rate as-is
            $totalInterest = $principal * $interest;
        }
        
        // Distribute interest equally over FIRST HALF of term
        // Edge case: If halfTerm=0 (1 period loan), all interest in first payment
        if ($halfTerm == 0) {
            $interestPerPeriodFirstHalf = $totalInterest;
            $halfTerm = 1; // Set to 1 so first payment gets all interest
        } else {
            $interestPerPeriodFirstHalf = $totalInterest / $halfTerm;
        }
        
        // Generate schedule
        for ($i = 1; $i <= $period; $i++) {
            // Interest only in FIRST HALF
            $interestAmount = ($i <= $halfTerm) ? $interestPerPeriodFirstHalf : 0;
            $installmentPayment = $principalPerPeriod + $interestAmount;
            
            $paymentDate = $this->calculatePaymentDate($disbursementDate, $i, $periodType);
            
            DB::table('loan_schedules')->insert([
                'loan_id' => $loan->id,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'principal' => round($principalPerPeriod, 2),
                'interest' => round($interestAmount, 2),
                'payment' => round($installmentPayment, 2),
                'balance' => round($balance, 2),
                'status' => 0,
                'paid' => 0,
                'pending_count' => 0,
                'date_created' => now(),
            ]);
            
            $balance -= $principalPerPeriod;
            $schedulesCreated++;
        }

        $this->info("  ✓ Created {$schedulesCreated} loan schedules");
    }

    /**
     * Calculate payment date based on period type
     */
    private function calculatePaymentDate($startDate, $periodNumber, $periodType)
    {
        $date = $startDate->copy();
        
        if ($periodType == 1) {
            // Weekly
            return $date->addWeeks($periodNumber);
        } elseif ($periodType == 2) {
            // Monthly
            return $date->addMonths($periodNumber);
        } else {
            // Daily (default)
            return $date->addDays($periodNumber);
        }
    }
}
