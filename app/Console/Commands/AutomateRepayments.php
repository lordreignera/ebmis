<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\PersonalLoan;
use App\Models\LoanSchedule;
use App\Services\MobileMoneyService;

class AutomateRepayments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'repayments:automate {--type=daily}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically initiate repayments at 3 PM for due schedules';

    /**
     * Mobile Money Service instance
     */
    protected $mobileMoneyService;

    /**
     * Create a new command instance.
     */
    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        parent::__construct();
        $this->mobileMoneyService = $mobileMoneyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $now = Carbon::now();
        
        Log::info("Automatic repayment initiated", ['type' => $type, 'time' => $now]);
        
        switch ($type) {
            case 'daily':
                $this->processDailyLoans();
                break;
            case 'weekly':
                $this->processWeeklyLoans();
                break;
            case 'monthly':
                $this->processMonthlyLoans();
                break;
            case 'retry':
                $this->processRetries();
                break;
            case 'late-fees':
                $this->generateLateFees();
                break;
            default:
                $this->error("Invalid type. Use: daily, weekly, monthly, retry, or late-fees");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Process daily loans (period_type = 3)
     */
    protected function processDailyLoans()
    {
        $this->info("Processing daily loans at 3 PM...");
        
        $schedules = $this->getDueSchedules(3); // Daily = period_type 3
        
        foreach ($schedules as $schedule) {
            $this->initiatePayment($schedule, 'daily');
        }
        
        $this->info("Processed " . count($schedules) . " daily loan schedules");
    }
    
    /**
     * Process weekly loans (period_type = 1) - Fridays only
     */
    protected function processWeeklyLoans()
    {
        if (!Carbon::now()->isFriday()) {
            $this->info("Not Friday - skipping weekly loans");
            return;
        }
        
        $this->info("Processing weekly loans on Friday at 3 PM...");
        
        $schedules = $this->getDueSchedules(1); // Weekly = period_type 1
        
        foreach ($schedules as $schedule) {
            $this->initiatePayment($schedule, 'weekly');
        }
        
        $this->info("Processed " . count($schedules) . " weekly loan schedules");
    }
    
    /**
     * Process monthly loans (period_type = 2)
     */
    protected function processMonthlyLoans()
    {
        $this->info("Processing monthly loans at 3 PM...");
        
        $schedules = $this->getDueSchedules(2); // Monthly = period_type 2
        
        foreach ($schedules as $schedule) {
            $this->initiatePayment($schedule, 'monthly');
        }
        
        $this->info("Processed " . count($schedules) . " monthly loan schedules");
    }
    
    /**
     * Get schedules due today for a specific loan type
     */
    protected function getDueSchedules($periodType)
    {
        $today = Carbon::today()->format('Y-m-d');
        
        return DB::table('loan_schedules as ls')
            ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
            ->join('products as p', 'pl.product_id', '=', 'p.id')
            ->join('members as m', 'pl.member_id', '=', 'm.id')
            ->where('p.period_type', $periodType)
            ->where('pl.status', 2) // Disbursed loans only
            ->where('ls.payment_date', '<=', $today) // Include overdue schedules
            ->where('ls.status', 0) // Unpaid schedules
            ->whereRaw('(ls.payment + ls.interest + ls.principal) > COALESCE(ls.paid, 0)') // Not fully paid
            ->select(
                'ls.id as schedule_id',
                'ls.loan_id',
                'ls.payment',
                'ls.payment_date',
                'm.id as member_id',
                'm.fname',
                'm.lname',
                'm.contact',
                'pl.code as loan_code',
                'p.name as product_name'
            )
            ->orderBy('ls.payment_date', 'asc') // Oldest first
            ->get();
    }
    
    /**
     * Initiate payment for a schedule
     */
    protected function initiatePayment($schedule, $type)
    {
        $phone = $this->formatPhone($schedule->contact);
        $amount = $schedule->payment;
        
        Log::info("Initiating payment", [
            'schedule_id' => $schedule->schedule_id,
            'loan_code' => $schedule->loan_code,
            'member' => $schedule->fname . ' ' . $schedule->lname,
            'phone' => $phone,
            'amount' => $amount,
            'type' => $type
        ]);
        
        // Check if already initiated today
        $existing = DB::table('auto_payment_requests')
            ->where('schedule_id', $schedule->schedule_id)
            ->whereDate('created_at', Carbon::today())
            ->first();
        
        if ($existing) {
            $this->warn("Payment already initiated for schedule {$schedule->schedule_id}");
            return;
        }
        
        // Create payment request record
        $requestId = DB::table('auto_payment_requests')->insertGetId([
            'schedule_id' => $schedule->schedule_id,
            'loan_id' => $schedule->loan_id,
            'member_id' => $schedule->member_id,
            'phone' => $phone,
            'amount' => $amount,
            'loan_type' => $type,
            'status' => 'initiated',
            'retry_count' => 0,
            'initiated_at' => Carbon::now(),
            'next_retry_at' => Carbon::now()->addMinutes(60),
            'created_at' => Carbon::now(),
        ]);
        
        // Send USSD push or SMS notification
        $this->sendPaymentRequest($phone, $amount, $schedule->loan_code, $requestId);
        
        $this->info("✓ Initiated payment for {$schedule->fname} {$schedule->lname} - Schedule #{$schedule->schedule_id}");
    }
    
    /**
     * Process retry attempts (run every hour)
     */
    protected function processRetries()
    {
        $this->info("Processing payment retries...");
        
        $now = Carbon::now();
        
        // Get requests that need retry
        $requests = DB::table('auto_payment_requests')
            ->where('status', 'initiated')
            ->where('retry_count', '<', 2) // Maximum 2 retries
            ->where('next_retry_at', '<=', $now)
            ->get();
        
        foreach ($requests as $request) {
            $schedule = DB::table('loan_schedules')->find($request->schedule_id);
            
            // Check if already paid
            if ($schedule && $schedule->status == 1) {
                DB::table('auto_payment_requests')
                    ->where('id', $request->id)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => Carbon::now()
                    ]);
                $this->info("✓ Schedule {$request->schedule_id} already paid");
                continue;
            }
            
            // Increment retry count
            $newRetryCount = $request->retry_count + 1;
            
            DB::table('auto_payment_requests')
                ->where('id', $request->id)
                ->update([
                    'retry_count' => $newRetryCount,
                    'next_retry_at' => Carbon::now()->addMinutes(60),
                    'last_retry_at' => Carbon::now()
                ]);
            
            // Send payment request again
            $this->sendPaymentRequest(
                $request->phone, 
                $request->amount, 
                DB::table('personal_loans')->where('id', $request->loan_id)->value('code'),
                $request->id
            );
            
            $this->info("↻ Retry #{$newRetryCount} for schedule {$request->schedule_id}");
            
            // If this was the last retry, mark for late fee generation
            if ($newRetryCount >= 2) {
                DB::table('auto_payment_requests')
                    ->where('id', $request->id)
                    ->update(['status' => 'failed']);
                    
                $this->warn("✗ Failed after 2 retries - schedule {$request->schedule_id}");
            }
        }
        
        $this->info("Processed " . count($requests) . " retry requests");
    }
    
    /**
     * Generate late fees at midnight for failed payments
     */
    protected function generateLateFees()
    {
        $this->info("Generating late fees at midnight...");
        
        // Get schedules that failed payment and need late fee
        $failedRequests = DB::table('auto_payment_requests')
            ->where('status', 'failed')
            ->whereNull('late_fee_generated_at')
            ->whereDate('initiated_at', '<', Carbon::today()) // From previous day
            ->get();
        
        foreach ($failedRequests as $request) {
            $schedule = DB::table('loan_schedules')->find($request->schedule_id);
            
            if (!$schedule) continue;
            
            // Check if still unpaid
            if ($schedule->status == 0) {
                // Late fee will be calculated automatically by the RepaymentController
                // Just mark that we've processed this request
                DB::table('auto_payment_requests')
                    ->where('id', $request->id)
                    ->update(['late_fee_generated_at' => Carbon::now()]);
                
                Log::info("Late fee will apply for schedule", [
                    'schedule_id' => $request->schedule_id,
                    'loan_id' => $request->loan_id
                ]);
                
                $this->info("⚠ Late fee applied for schedule {$request->schedule_id}");
            } else {
                // Paid in the meantime
                DB::table('auto_payment_requests')
                    ->where('id', $request->id)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => Carbon::now()
                    ]);
            }
        }
        
        $this->info("Processed " . count($failedRequests) . " late fee generations");
    }
    
    /**
     * Send payment request via Mobile Money (FlexiPay/Stanbic)
     */
    protected function sendPaymentRequest($phone, $amount, $loanCode, $requestId)
    {
        $formattedPhone = $this->formatPhone($phone);
        
        Log::info("Sending automatic payment request", [
            'phone' => $formattedPhone,
            'amount' => $amount,
            'loan_code' => $loanCode,
            'request_id' => $requestId
        ]);
        
        try {
            // Get loan and schedule details
            $paymentRequest = DB::table('auto_payment_requests')->find($requestId);
            
            $loan = DB::table('personal_loans as pl')
                ->join('members as m', 'pl.member_id', '=', 'm.id')
                ->where('pl.code', $loanCode)
                ->select('m.fname', 'm.lname', 'pl.id', 'pl.code', 'm.id as member_id')
                ->first();
            
            if (!$loan) {
                Log::error("Loan not found for automatic payment", ['loan_code' => $loanCode]);
                return;
            }
            
            $memberName = trim($loan->fname . ' ' . $loan->lname);
            $description = "Auto payment for loan {$loanCode}";
            
            // Use MobileMoneyService to collect money (same as manual collection)
            $result = $this->mobileMoneyService->collectMoney(
                $memberName,
                $formattedPhone,
                $amount,
                $description
            );
            
            // Update request with transaction details
            DB::table('auto_payment_requests')
                ->where('id', $requestId)
                ->update([
                    'transaction_ref' => $result['reference'] ?? null,
                    'flexipay_ref' => $result['flexipay_ref'] ?? null,
                    'api_status_code' => $result['status_code'] ?? null,
                    'api_message' => $result['message'] ?? null,
                    'api_response' => json_encode($result),
                    'updated_at' => Carbon::now()
                ]);
            
            if ($result['success']) {
                // Create Repayment record so callback can find it and update status
                // This matches the manual repayment flow
                $transactionRef = $result['reference'];
                $formattedPhone = $this->formatPhone($phone);
                
                DB::table('repayments')->insert([
                    'schedule_id' => $paymentRequest->schedule_id,
                    'loan_id' => $loan->id,
                    'amount' => $amount,
                    'type' => 2, // Mobile Money
                    'status' => '0', // Pending - will be approved by callback
                    'txn_id' => $transactionRef,
                    'transaction_reference' => $transactionRef,
                    'pay_status' => 'PENDING',
                    'pay_message' => 'Automatic payment initiated - awaiting confirmation',
                    'payment_phone' => $formattedPhone,
                    'added_by' => 1, // System user
                    'date_created' => Carbon::now()
                ]);
                
                // Also create raw_payments record for tracking (bimsadmin style)
                DB::table('raw_payments')->insert([
                    'trans_id' => $transactionRef,
                    'phone' => $formattedPhone,
                    'amount' => $amount,
                    'ref' => $result['flexipay_ref'] ?? '',
                    'message' => 'Automatic payment initiated',
                    'status' => 'Processed',
                    'pay_status' => '00',
                    'pay_message' => 'Completed successfully',
                    'date_created' => Carbon::now(),
                    'type' => 'repayment',
                    'direction' => 'cash_in',
                    'added_by' => 1,
                    'raw_message' => serialize([
                        'schedule_id' => $paymentRequest->schedule_id, 
                        'loan_id' => $loan->id, 
                        'member_id' => $loan->member_id,
                        'auto_payment_request_id' => $requestId
                    ]),
                ]);
                
                Log::info("Payment request sent successfully", [
                    'request_id' => $requestId,
                    'reference' => $transactionRef,
                    'message' => $result['message'] ?? 'Success',
                    'repayment_created' => true
                ]);
                
                $this->info("  → Payment request sent: {$transactionRef}");
                
                // Poll for payment status (like manual payments) - wait 60 seconds
                $this->info("  → Polling for status confirmation...");
                $this->pollPaymentStatus($transactionRef, $requestId, $paymentRequest->schedule_id, 12); // 12 attempts x 5 seconds = 60 seconds
                
            } else {
                Log::warning("Payment request failed", [
                    'request_id' => $requestId,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);
                
                $this->warn("  → Failed: {$result['message']}");
                
                // Mark as failed if it's a permanent error
                if (in_array($result['status_code'] ?? '', ['INVALID_PHONE', 'INVALID_AMOUNT'])) {
                    DB::table('auto_payment_requests')
                        ->where('id', $requestId)
                        ->update(['status' => 'failed']);
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Exception sending automatic payment request", [
                'request_id' => $requestId,
                'phone' => $formattedPhone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            $this->error("  → Exception: {$e->getMessage()}");
        }
    }
    
    /**
     * Format phone number
     */
    protected function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) == 9) {
            return '256' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Poll payment status after initiation (like manual payments)
     */
    protected function pollPaymentStatus($transactionRef, $requestId, $scheduleId, $maxAttempts = 12)
    {
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            $attempt++;
            sleep(5); // Wait 5 seconds between checks
            
            // Check repayment status in database
            $repayment = DB::table('repayments')
                ->where('txn_id', $transactionRef)
                ->orWhere('transaction_reference', $transactionRef)
                ->first();
            
            if ($repayment && $repayment->status == 1) {
                $this->info("  → ✓ Payment confirmed in database!");
                
                // Update auto_payment_requests
                DB::table('auto_payment_requests')
                    ->where('id', $requestId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => Carbon::now()
                    ]);
                
                return true;
            }
            
            // Check status with FlexiPay/Stanbic API
            $statusResult = $this->mobileMoneyService->checkTransactionStatus($transactionRef);
            
            if ($statusResult['success'] && $statusResult['status'] === 'completed') {
                $this->info("  → ✓ Payment confirmed by FlexiPay!");
                
                // Update repayment if not already updated by callback
                if ($repayment && $repayment->status == 0) {
                    DB::beginTransaction();
                    try {
                        DB::table('repayments')
                            ->where('id', $repayment->id)
                            ->update([
                                'status' => '1',
                                'pay_status' => 'SUCCESS',
                                'pay_message' => 'Payment completed (auto-polling)',
                                'updated_at' => Carbon::now()
                            ]);
                        
                        // Update schedule
                        $schedule = DB::table('loan_schedules')->find($scheduleId);
                        if ($schedule) {
                            $newPaid = ($schedule->paid ?? 0) + $repayment->amount;
                            $isFullyPaid = $newPaid >= $schedule->payment;
                            
                            DB::table('loan_schedules')
                                ->where('id', $scheduleId)
                                ->update([
                                    'paid' => $newPaid,
                                    'status' => $isFullyPaid ? 1 : 0,
                                    'date_cleared' => $isFullyPaid ? Carbon::now() : null
                                ]);
                            
                            $this->info("  → ✓ Updated schedule #{$scheduleId}");
                        }
                        
                        // Update auto_payment_requests
                        DB::table('auto_payment_requests')
                            ->where('id', $requestId)
                            ->update([
                                'status' => 'completed',
                                'completed_at' => Carbon::now()
                            ]);
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error("Failed to update payment status", [
                            'transaction_ref' => $transactionRef,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return true;
            } elseif ($statusResult['success'] && $statusResult['status'] === 'failed') {
                $this->warn("  → ✗ Payment failed: {$statusResult['message']}");
                
                DB::table('auto_payment_requests')
                    ->where('id', $requestId)
                    ->update(['status' => 'failed']);
                
                return false;
            }
            
            // Still pending...
            if ($attempt % 3 == 0) { // Show progress every 15 seconds
                $this->info("  → [{$attempt}/{$maxAttempts}] Still pending...");
            }
        }
        
        $this->warn("  → Timeout after 60 seconds - will retry later");
        return false;
    }
}
