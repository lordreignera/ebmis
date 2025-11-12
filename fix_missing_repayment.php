<?php
/**
 * Fix missing repayment record for successful mobile money payment
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Fixing missing repayment for transaction EbP1762901238...\n";
echo "========================================\n\n";

DB::beginTransaction();

try {
    // Get the raw_payment record
    $rawPayment = DB::table('raw_payments')
        ->where('trans_id', 'EbP1762901238')
        ->first();
    
    if (!$rawPayment) {
        echo "❌ Raw payment not found!\n";
        exit(1);
    }
    
    echo "Found raw payment:\n";
    echo "  Transaction ID: {$rawPayment->trans_id}\n";
    echo "  Amount: {$rawPayment->amount}\n";
    echo "  Status: {$rawPayment->pay_status}\n";
    
    // Unserialize the raw_message to get schedule and loan info
    $rawMessage = unserialize($rawPayment->raw_message);
    $scheduleId = $rawMessage['schedule_id'];
    $loanId = $rawMessage['loan_id'];
    $memberId = $rawMessage['member_id'];
    
    echo "  Schedule ID: {$scheduleId}\n";
    echo "  Loan ID: {$loanId}\n";
    echo "  Member ID: {$memberId}\n\n";
    
    // Get schedule details
    $schedule = DB::table('loan_schedules')->where('id', $scheduleId)->first();
    
    if (!$schedule) {
        echo "❌ Schedule not found!\n";
        exit(1);
    }
    
    echo "Schedule details:\n";
    echo "  Payment amount: {$schedule->payment}\n";
    echo "  Current status: {$schedule->status}\n";
    echo "  Pending count: {$schedule->pending_count}\n\n";
    
    // Create the repayment record
    $repaymentId = DB::table('repayments')->insertGetId([
        'type' => 2, // Mobile money
        'details' => 'Mobile money repayment',
        'loan_id' => $loanId,
        'schedule_id' => $scheduleId,
        'amount' => $rawPayment->amount,
        'date_created' => $rawPayment->date_created,
        'added_by' => $rawPayment->added_by,
        'status' => 1, // Confirmed
        'platform' => 'Web',
        'txn_id' => $rawPayment->trans_id,
        'pay_status' => 'SUCCESS',
        'pay_message' => 'Payment confirmed via mobile money',
        'raw_message' => $rawPayment->raw_message
    ]);
    
    echo "✅ Created repayment record ID: {$repaymentId}\n\n";
    
    // Update schedule - mark as paid and decrement pending_count
    DB::table('loan_schedules')
        ->where('id', $scheduleId)
        ->update([
            'status' => 1, // Mark as paid
            'pending_count' => DB::raw('GREATEST(pending_count - 1, 0)'), // Decrement but don't go below 0
            'txn_id' => $rawPayment->trans_id,
            'date_cleared' => now()
        ]);
    
    echo "✅ Updated schedule status to PAID\n";
    
    // Update raw_payment status
    DB::table('raw_payments')
        ->where('trans_id', $rawPayment->trans_id)
        ->update(['pay_status' => '01']); // Success
    
    echo "✅ Updated raw_payment status\n\n";
    
    // Check if all schedules are paid - if so, mark loan as completed
    $unpaidSchedules = DB::table('loan_schedules')
        ->where('loan_id', $loanId)
        ->where('status', 0)
        ->count();
    
    if ($unpaidSchedules == 0) {
        DB::table('personal_loans')
            ->where('id', $loanId)
            ->update(['status' => 3]); // Completed
        
        echo "✅ All schedules paid - Loan marked as completed\n";
    } else {
        echo "ℹ️  Loan still has {$unpaidSchedules} unpaid schedule(s)\n";
    }
    
    DB::commit();
    
    echo "\n========================================\n";
    echo "✅ Repayment fixed successfully!\n";
    echo "Repayment ID: {$repaymentId}\n";
    echo "Amount: UGX " . number_format($rawPayment->amount, 0) . "\n";
    echo "========================================\n\n";
    
    echo "Refresh the page to see the updated schedule!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
