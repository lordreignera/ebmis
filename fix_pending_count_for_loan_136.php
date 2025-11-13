<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking for pending mobile money repayments for loan 136...\n\n";

// Find pending mobile money repayments
$pendingRepayments = DB::table('repayments')
    ->where('loan_id', 136)
    ->where('type', 2) // Mobile Money
    ->where('payment_status', 'Pending')
    ->get(['id', 'schedule_id', 'amount', 'transaction_reference', 'date_created']);

if ($pendingRepayments->isEmpty()) {
    echo "No pending mobile money repayments found.\n";
} else {
    echo "Found " . $pendingRepayments->count() . " pending repayment(s):\n";
    
    foreach ($pendingRepayments as $repayment) {
        echo "\nRepayment ID: {$repayment->id}\n";
        echo "Schedule ID: {$repayment->schedule_id}\n";
        echo "Amount: {$repayment->amount}\n";
        echo "Transaction Ref: {$repayment->transaction_reference}\n";
        echo "Created: {$repayment->date_created}\n";
        
        // Check current pending_count
        $schedule = DB::table('loan_schedules')
            ->where('id', $repayment->schedule_id)
            ->first(['id', 'pending_count']);
        
        echo "Current pending_count: {$schedule->pending_count}\n";
        
        // Increment pending_count if it's 0
        if ($schedule->pending_count == 0) {
            DB::table('loan_schedules')
                ->where('id', $repayment->schedule_id)
                ->increment('pending_count');
            
            echo "✓ Incremented pending_count for schedule {$repayment->schedule_id}\n";
        } else {
            echo "✓ Schedule already has pending_count = {$schedule->pending_count}\n";
        }
    }
}

echo "\n\nDone!\n";
