<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Services\MobileMoneyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-pending
                            {--fee= : Check specific fee ID}
                            {--limit=50 : Maximum number of fees to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check status of pending mobile money payments with FlexiPay (like old system check_tran.php)';

    protected MobileMoneyService $mobileMoneyService;

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
        $this->info('🔍 Checking pending mobile money payments...');
        
        $query = Fee::where('payment_type', 1) // Mobile Money
                    ->where('status', 0) // Pending
                    ->whereNotNull('pay_ref')
                    ->whereNotNull('payment_raw');
        
        // Check specific fee if provided
        if ($feeId = $this->option('fee')) {
            $query->where('id', $feeId);
        } else {
            // Only check recent fees (last 24 hours)
            $query->where('date_added', '>=', now()->subDay());
        }
        
        $pendingFees = $query->limit($this->option('limit'))->get();
        
        if ($pendingFees->isEmpty()) {
            $this->info('✅ No pending mobile money payments found');
            return 0;
        }
        
        $this->info("Found {$pendingFees->count()} pending payment(s)");
        $this->newLine();
        
        $checked = 0;
        $completed = 0;
        $failed = 0;
        $stillPending = 0;
        
        foreach ($pendingFees as $fee) {
            try {
                $this->line("Checking Fee #{$fee->id} - Ref: {$fee->pay_ref}");
                
                // Check transaction status with FlexiPay
                $statusResult = $this->mobileMoneyService->checkTransactionStatus($fee->pay_ref);
                
                if (!$statusResult['success']) {
                    $this->warn("  ⚠️  Failed to check status: {$statusResult['message']}");
                    continue;
                }
                
                $status = $statusResult['status'];
                $message = $statusResult['message'];
                
                $this->line("  Status: {$status} - {$message}");
                
                // Update fee based on status
                if ($status === 'completed') {
                    $fee->update([
                        'status' => 1, // Paid
                        'payment_raw' => json_encode($statusResult['raw_response']),
                        'date_paid' => now()
                    ]);
                    
                    $this->info("  ✅ Marked as PAID");
                    $completed++;
                    
                    // Log success
                    Log::info("Payment completed via polling", [
                        'fee_id' => $fee->id,
                        'transaction_ref' => $fee->pay_ref,
                        'amount' => $fee->amount,
                        'member_id' => $fee->member_id
                    ]);
                    
                } elseif ($status === 'failed') {
                    $fee->update([
                        'status' => 2, // Failed
                        'payment_raw' => json_encode($statusResult['raw_response'])
                    ]);
                    
                    $this->error("  ❌ Marked as FAILED");
                    $failed++;
                    
                    // Log failure
                    Log::warning("Payment failed via polling", [
                        'fee_id' => $fee->id,
                        'transaction_ref' => $fee->pay_ref,
                        'amount' => $fee->amount,
                        'message' => $message
                    ]);
                    
                } else {
                    $this->comment("  ⏳ Still pending...");
                    $stillPending++;
                }
                
                $checked++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error: {$e->getMessage()}");
                
                Log::error("Error checking payment status", [
                    'fee_id' => $fee->id,
                    'transaction_ref' => $fee->pay_ref,
                    'error' => $e->getMessage()
                ]);
            }
            
            $this->newLine();
        }
        
        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Checked', $checked],
                ['✅ Completed', $completed],
                ['❌ Failed', $failed],
                ['⏳ Still Pending', $stillPending],
            ]
        );
        
        return 0;
    }
}
