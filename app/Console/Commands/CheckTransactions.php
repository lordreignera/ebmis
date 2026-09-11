<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Services\MobileMoneyService;
use App\Services\RepaymentService;

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
    protected $directPendingCheckedCount = 0;
    protected $directPendingFailedCount = 0;
    protected RepaymentService $repaymentService;
    protected MobileMoneyService $mobileMoneyService;

    public function __construct(RepaymentService $repaymentService, MobileMoneyService $mobileMoneyService)
    {
        parent::__construct();
        $this->repaymentService = $repaymentService;
        $this->mobileMoneyService = $mobileMoneyService;
    }

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

        $this->info('Found ' . $transactions->count() . ' pending raw payment transaction(s).');

        foreach ($transactions as $transaction) {
            $this->processTransaction($transaction);
        }

        $this->processDirectPendingRepayments();

        $this->info("Summary: RawProcessed={$this->processedCount}, DirectChecked={$this->directPendingCheckedCount}, Auto-approved={$this->autoApprovedCount}, Failed={$this->directPendingFailedCount}");
        
        Log::info('Transaction check completed', [
            'processed' => $this->processedCount,
            'direct_pending_checked' => $this->directPendingCheckedCount,
            'auto_approved' => $this->autoApprovedCount,
            'failed' => $this->directPendingFailedCount,
        ]);

        return 0;
    }

    /**
     * Reconcile newer repayment rows that were created directly without a raw_payments row.
     * This protects online repayments when the browser polling is closed or the provider
     * callback does not reach the app.
     */
    protected function processDirectPendingRepayments(): void
    {
        $query = DB::table('repayments')
            ->where('type', 2)
            ->where('status', 0)
            ->where(function ($query) {
                $query->where('payment_status', 'Pending')
                    ->orWhere('pay_status', 'PENDING');
            })
            ->where(function ($query) {
                $query->whereNotNull('txn_id')
                    ->orWhereNotNull('transaction_reference');
            })
            ->orderBy('id');

        if ($this->option('txn')) {
            $transactionRef = $this->option('txn');
            $query->where(function ($query) use ($transactionRef) {
                $query->where('txn_id', $transactionRef)
                    ->orWhere('transaction_reference', $transactionRef);
            });
        } else {
            $query->where('date_created', '>=', Carbon::now()->subDays(7));
        }

        $repayments = $query->limit(100)->get();

        if ($repayments->isEmpty()) {
            $this->info('No direct pending repayment rows found.');
            return;
        }

        $this->info('Found ' . $repayments->count() . ' direct pending repayment row(s).');

        foreach ($repayments as $repayment) {
            $this->processDirectPendingRepayment($repayment);
        }
    }

    protected function processDirectPendingRepayment(object $repayment): void
    {
        $transactionRef = $repayment->txn_id ?: $repayment->transaction_reference;

        if (!$transactionRef) {
            return;
        }

        $this->directPendingCheckedCount++;
        $this->info("Checking repayment #{$repayment->id} - Ref: {$transactionRef}");

        try {
            $network = null;
            if (!empty($repayment->payment_phone)) {
                $network = $this->mobileMoneyService->detectNetwork($repayment->payment_phone);
            }

            $statusResult = $this->mobileMoneyService->checkTransactionStatus($transactionRef, $network);

            if (!($statusResult['success'] ?? false)) {
                $this->warn('  Status check failed: ' . ($statusResult['message'] ?? 'Unknown error'));
                return;
            }

            $status = $statusResult['status'] ?? 'pending';
            $statusCode = (string) ($statusResult['status_code'] ?? '');
            $message = $statusResult['message'] ?? '';

            $this->line("  Status: {$status} {$statusCode} - {$message}");

            if ($status === 'completed') {
                $result = $this->repaymentService->approveRepayment(
                    (int) $repayment->id,
                    $statusCode !== '' ? $statusCode : '00',
                    $message ?: 'Payment confirmed by scheduled checker'
                );

                if ($result['success']) {
                    $this->autoApprovedCount++;
                    $this->info('  Repayment marked as PAID');
                    if ($repayment->schedule_id) {
                        $this->syncSchedulePendingCount((int) $repayment->schedule_id);
                    }
                    return;
                }

                Log::error('Scheduled checker could not approve completed direct repayment', [
                    'repayment_id' => $repayment->id,
                    'transaction_ref' => $transactionRef,
                    'message' => $result['message'] ?? 'Unknown approval error',
                ]);

                $this->error('  Gateway completed, but local approval failed: ' . ($result['message'] ?? 'Unknown approval error'));
                return;
            }

            if ($status === 'failed') {
                $createdAt = $repayment->date_created ? Carbon::parse($repayment->date_created) : Carbon::now()->subMinutes(10);

                if ($createdAt->diffInMinutes(now()) < 2) {
                    $this->comment('  Still inside gateway retry window; leaving pending.');
                    return;
                }

                DB::table('repayments')
                    ->where('id', $repayment->id)
                    ->update([
                        'status' => 2,
                        'payment_status' => 'Failed',
                        'pay_status' => 'FAILED',
                        'pay_message' => $message,
                        'payment_raw' => json_encode($statusResult['raw_response'] ?? $statusResult),
                    ]);

                if ($repayment->schedule_id) {
                    $this->syncSchedulePendingCount((int) $repayment->schedule_id);
                }

                $this->directPendingFailedCount++;
                $this->error('  Repayment marked as FAILED');
                return;
            }

            $this->comment('  Still pending.');
        } catch (\Exception $e) {
            $this->error("  Error: {$e->getMessage()}");
            Log::error('Direct repayment status check error', [
                'repayment_id' => $repayment->id,
                'transaction_ref' => $transactionRef,
                'error' => $e->getMessage(),
            ]);
        }
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
                                'payment_status' => 'Failed',
                                'pay_status' => 'FAILED',
                                'pay_message' => $statusDesc
                            ]);
                        
                        if ($repayment->schedule_id) {
                            $this->syncSchedulePendingCount((int) $repayment->schedule_id);
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

        if ((int) $repayment->status === 1) {
            $this->info("  Repayment already approved");
            $this->markRawPaymentCompleted($transId, $statusDesc ?: 'Payment already approved');
            if ($repayment->schedule_id) {
                $this->syncSchedulePendingCount((int) $repayment->schedule_id);
            }
            return;
        }

        $result = $this->repaymentService->approveRepayment(
            (int) $repayment->id,
            'SUCCESS',
            $statusDesc ?: 'Payment confirmed by transaction checker'
        );

        if ($result['success']) {
            $this->autoApprovedCount++;
            $this->info("  Repayment approved through RepaymentService");
            $this->markRawPaymentCompleted($transId, $statusDesc ?: 'Payment confirmed by transaction checker');
            if ($repayment->schedule_id) {
                $this->syncSchedulePendingCount((int) $repayment->schedule_id);
            }
            return;
        }

        Log::error('Transaction checker could not approve completed repayment', [
            'repayment_id' => $repayment->id,
            'trans_id' => $transId,
            'message' => $result['message'] ?? 'Unknown approval error',
        ]);

        $this->error("  Gateway completed, but local approval failed: " . ($result['message'] ?? 'Unknown approval error'));
        return;

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

    private function syncSchedulePendingCount(int $scheduleId): void
    {
        $remainingPending = DB::table('repayments')
            ->where('schedule_id', $scheduleId)
            ->where('type', 2)
            ->where('status', 0)
            ->where(function ($query) {
                $query->where('payment_status', 'Pending')
                    ->orWhere('pay_status', 'PENDING');
            })
            ->count();

        DB::table('loan_schedules')
            ->where('id', $scheduleId)
            ->update(['pending_count' => $remainingPending]);
    }

    private function markRawPaymentCompleted(string $transId, string $message): void
    {
        DB::table('raw_payments')
            ->where('trans_id', $transId)
            ->update([
                'pay_status' => '01',
                'pay_message' => $message,
                'pay_date' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
    }
}
