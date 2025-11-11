<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CheckTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:check 
                            {--txn= : Specific transaction ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check pending raw_payments transactions and auto-process successful repayments';

    protected $processedCount = 0;
    protected $autoApprovedCount = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting transaction status check...');
        
        // Get pending transactions
        $transactions = DB::table('raw_payments')
            ->where('pay_status', '00')
            ->where('type', 'repayment');

        // If specific transaction specified
        if ($this->option('txn')) {
            $transactions->where('trans_id', $this->option('txn'));
        }

        $transactions = $transactions->get();

        if ($transactions->isEmpty()) {
            $this->info('No pending transactions found.');
            return 0;
        }

        $this->info('Found ' . $transactions->count() . ' pending transactions.');

        foreach ($transactions as $transaction) {
            $this->processTransaction($transaction);
        }

        $this->info("Summary: Processed={$this->processedCount}, Auto-approved={$this->autoApprovedCount}");
        
        Log::info('Transaction check completed', [
            'processed' => $this->processedCount,
            'auto_approved' => $this->autoApprovedCount
        ]);

        return 0;
    }

    /**
     * Process a single transaction
     */
    protected function processTransaction($transaction)
    {
        if (empty($transaction->trans_id)) {
            $this->warn("Skipping transaction ID {$transaction->id} (empty trans_id)");
            return;
        }

        $this->info("Checking transaction {$transaction->trans_id}...");

        // Query FlexiPay for status
        try {
            $response = Http::timeout(30)
                ->asForm()
                ->withOptions(['verify' => false]) // Disable SSL verification for local dev
                ->post('https://emuria.net/flexipay/checkFromMMStatusProd.php', [
                    'reference' => $transaction->trans_id
                ]);

            if (!$response->successful()) {
                $this->error("HTTP error for {$transaction->trans_id}: " . $response->status());
                return;
            }

            $data = $response->json();

            if (!isset($data['statusCode'])) {
                $this->error("Invalid response for {$transaction->trans_id}");
                Log::error('Invalid FlexiPay response', [
                    'trans_id' => $transaction->trans_id,
                    'response' => $response->body()
                ]);
                return;
            }

            $statusCode = (string)$data['statusCode'];
            $statusDesc = $data['statusDescription'] ?? '';

            $this->info("  Status: {$statusCode} - {$statusDesc}");

            // Update raw_payments
            DB::table('raw_payments')
                ->where('id', $transaction->id)
                ->update([
                    'status' => 'Processed', // Keep as 'Processed' (bimsadmin format)
                    'pay_status' => $statusCode, // This will be '01' when successful, '57' when failed
                    'pay_message' => $statusDesc,
                    'pay_date' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

            $this->processedCount++;

            // Process if payment is successful (statusCode = '00' or '01')
            if (in_array($statusCode, ['00', '01'])) {
                $this->processSuccessfulPayment($transaction->trans_id, $statusCode, $statusDesc);
            } else {
                $this->info("  Payment failed or still pending (status: {$statusCode})");
                // If status is '57' (FAILED) or other terminal status, mark repayment as failed
                if (in_array($statusCode, ['57', '58', '59'])) {
                    // Mark any associated repayment as failed
                    $repayment = DB::table('repayments')
                        ->where('txn_id', $transaction->trans_id)
                        ->first();
                    
                    if ($repayment) {
                        DB::table('repayments')
                            ->where('id', $repayment->id)
                            ->update([
                                'status' => 2, // Failed
                                'pay_status' => 'FAILED',
                                'pay_message' => $statusDesc
                            ]);
                        
                        // Decrement pending_count on schedule
                        if ($repayment->schedule_id) {
                            DB::table('loan_schedules')
                                ->where('id', $repayment->schedule_id)
                                ->decrement('pending_count');
                        }
                        
                        $this->info("  Repayment marked as FAILED");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("Error processing {$transaction->trans_id}: " . $e->getMessage());
            Log::error('Transaction check error', [
                'trans_id' => $transaction->trans_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process successful payment - update repayment and schedules (bimsadmin style)
     */
    protected function processSuccessfulPayment($transId, $statusCode, $statusDesc)
    {
        // Find repayment record
        $repayment = DB::table('repayments')
            ->where('txn_id', $transId)
            ->first();

        if (!$repayment) {
            $this->warn("  No repayment record found for txn {$transId}");
            return;
        }

        $this->info("  Processing repayment ID {$repayment->id}");
        $this->autoApprovedCount++;

        // Update repayment status to confirmed (1)
        DB::table('repayments')
            ->where('id', $repayment->id)
            ->update([
                'status' => 1, // Confirmed/Approved
                'pay_status' => 'SUCCESS',
                'pay_message' => $statusDesc
            ]);

        // Get schedule details
        $schedule = DB::table('loan_schedules')
            ->where('id', $repayment->schedule_id)
            ->first();

        if (!$schedule) {
            $this->warn("  Schedule not found: {$repayment->schedule_id}");
            return;
        }

        // Update schedule: increment paid amount
        $currentPaid = floatval($schedule->paid);
        $newPaid = $currentPaid + floatval($repayment->amount);
        $totalPayment = floatval($schedule->payment);

        $this->info("  Schedule paid: {$currentPaid} + {$repayment->amount} = {$newPaid} (total: {$totalPayment})");

        // Decrement pending_count
        $newPendingCount = max(0, intval($schedule->pending_count) - 1);

        // Check if schedule is fully paid (allow 500 UGX tolerance for rounding)
        $remainingBalance = $totalPayment - $newPaid;
        $isFullyPaid = $remainingBalance <= 500;

        if ($isFullyPaid) {
            DB::table('loan_schedules')
                ->where('id', $repayment->schedule_id)
                ->update([
                    'paid' => $newPaid,
                    'pending_count' => $newPendingCount,
                    'status' => 1, // Fully paid
                    'date_cleared' => Carbon::now()
                ]);
            $this->info("  Schedule marked as FULLY PAID (remaining: {$remainingBalance})");
        } else {
            DB::table('loan_schedules')
                ->where('id', $repayment->schedule_id)
                ->update([
                    'paid' => $newPaid,
                    'pending_count' => $newPendingCount
                    // status remains 0 (not fully paid)
                ]);
            $this->info("  Schedule partially paid: {$newPaid}/{$totalPayment} (remaining: {$remainingBalance})");
        }

        // Check if all schedules for this loan are paid
        $unpaidSchedules = DB::table('loan_schedules')
            ->where('loan_id', $repayment->loan_id)
            ->where('status', 0)
            ->count();

        if ($unpaidSchedules === 0) {
            // All schedules paid - close the loan
            $loanTable = $repayment->type == 1 ? 'personal_loans' : 'group_loans';
            
            DB::table($loanTable)
                ->where('id', $repayment->loan_id)
                ->update([
                    'status' => 3, // Completed
                    'date_closed' => Carbon::now()
                ]);

            $this->info("  All schedules paid! Loan {$repayment->loan_id} closed.");
        }

        // Log trail
        DB::table('trail')->insert([
            'action' => "Auto reconciled repayment: repayment_id={$repayment->id};txn={$transId}",
            'date_created' => Carbon::now(),
            'ip_address' => '127.0.0.1',
            'userid' => 0,
            'change_vals' => "repayment_id={$repayment->id};loan_id={$repayment->loan_id};amount={$repayment->amount}"
        ]);
    }
}
