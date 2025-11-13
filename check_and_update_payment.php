<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Repayment;
use App\Models\LoanSchedule;
use App\Models\Loan;

echo "Checking repayment status for transaction EbP1762994337...\n\n";

// Find the repayment
$repayment = Repayment::where('transaction_reference', 'EbP1762994337')->first();

if (!$repayment) {
    echo "Repayment not found!\n";
    exit;
}

echo "Repayment ID: {$repayment->id}\n";
echo "Current Status: {$repayment->payment_status}\n";
echo "Amount: {$repayment->amount}\n";
echo "Schedule ID: {$repayment->schedule_id}\n\n";

// Get the schedule
$schedule = LoanSchedule::find($repayment->schedule_id);
echo "Schedule Status: " . ($schedule->status == 1 ? 'Paid' : 'Not Paid') . "\n";
echo "Schedule Paid Amount: {$schedule->paid}\n";
echo "Schedule Payment Due: {$schedule->payment}\n";
echo "Schedule Pending Count: {$schedule->pending_count}\n\n";

// Manually update if payment is completed but schedule is not updated
if ($repayment->payment_status === 'Completed' && $schedule->status == 0) {
    echo "Payment is completed but schedule not updated. Updating now...\n\n";
    
    DB::beginTransaction();
    try {
        // Decrement pending count
        if ($schedule->pending_count > 0) {
            $schedule->decrement('pending_count');
            echo "✓ Decremented pending_count\n";
        }
        
        // Update schedule paid amount
        $schedule->increment('paid', $repayment->amount);
        echo "✓ Incremented paid amount by {$repayment->amount}\n";
        
        // Refresh to get updated values
        $schedule->refresh();
        echo "✓ New paid amount: {$schedule->paid}\n";
        
        // Check if fully paid
        if ($schedule->paid >= $schedule->payment) {
            $schedule->update(['status' => 1]);
            echo "✓ Marked schedule as PAID\n";
        }
        
        DB::commit();
        echo "\n✓✓✓ Update completed successfully!\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} elseif ($repayment->payment_status === 'Pending') {
    echo "Payment is still PENDING. Check FlexiPay status first.\n";
} else {
    echo "Schedule already updated correctly.\n";
}
