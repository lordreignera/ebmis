<?php
/**
 * Manually Approve Isaac's Second Payment
 * 
 * Approve the pending payment and update schedule/loan status
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Manually Approve Isaac's Second Payment ===\n\n";

$repaymentId = 947;
$loanId = 133;
$scheduleId = 2976;

try {
    DB::beginTransaction();
    
    // Get the repayment
    $repayment = DB::table('repayments')->where('id', $repaymentId)->first();
    
    if (!$repayment) {
        echo "❌ Repayment {$repaymentId} not found!\n";
        exit(1);
    }
    
    echo "Found Repayment:\n";
    echo "  ID: {$repayment->id}\n";
    echo "  Amount: " . number_format($repayment->amount, 2) . " UGX\n";
    echo "  Current Status: " . ($repayment->status == 0 ? 'PENDING' : 'APPROVED') . "\n";
    echo "  Reference: " . ($repayment->transaction_reference ?? $repayment->txn_id ?? 'N/A') . "\n";
    echo "\n";
    
    if ($repayment->status == 1) {
        echo "✓ Already approved!\n";
        DB::rollBack();
        exit(0);
    }
    
    // Step 1: Approve the repayment
    echo "Step 1: Approving repayment...\n";
    DB::table('repayments')
        ->where('id', $repaymentId)
        ->update([
            'status' => 1,
            'pay_status' => 'SUCCESS',
            'pay_message' => 'Manually approved'
        ]);
    echo "  ✓ Repayment approved\n\n";
    
    // Step 2: Skip updating loan paid amount (column doesn't exist in personal_loans)
    echo "Step 2: Skipping loan paid amount (tracked in schedules)...\n";
    echo "  ✓ Loan balance calculated from schedules\n\n";
    
    // Step 3: Update schedule paid amount
    echo "Step 3: Updating schedule...\n";
    $schedule = DB::table('loan_schedules')->where('id', $scheduleId)->first();
    
    if ($schedule) {
        $currentPaid = floatval($schedule->paid ?? 0);
        $newPaid = $currentPaid + $repayment->amount;
        $scheduleDue = floatval($schedule->payment);
        
        echo "  Schedule Due: " . number_format($scheduleDue, 2) . " UGX\n";
        echo "  Current Paid: " . number_format($currentPaid, 2) . " UGX\n";
        echo "  Payment Amount: " . number_format($repayment->amount, 2) . " UGX\n";
        echo "  New Paid: " . number_format($newPaid, 2) . " UGX\n";
        
        DB::table('loan_schedules')
            ->where('id', $scheduleId)
            ->update(['paid' => $newPaid]);
        
        // Check if schedule should be marked as paid
        if ($newPaid >= $scheduleDue) {
            echo "  Schedule is fully paid! Marking as complete...\n";
            DB::table('loan_schedules')
                ->where('id', $scheduleId)
                ->update([
                    'status' => 1,
                    'date_cleared' => now()
                ]);
            echo "  ✓ Schedule marked as PAID\n";
            
            // Check for overpayment
            if ($newPaid > $scheduleDue) {
                $overpayment = $newPaid - $scheduleDue;
                echo "  ⚠️  Overpayment: " . number_format($overpayment, 2) . " UGX\n";
                
                // Cap current schedule
                DB::table('loan_schedules')
                    ->where('id', $scheduleId)
                    ->update(['paid' => $scheduleDue]);
                
                // Apply to next schedule
                $nextSchedule = DB::table('loan_schedules')
                    ->where('loan_id', $loanId)
                    ->where('status', '!=', 1)
                    ->where('id', '>', $scheduleId)
                    ->orderBy('id')
                    ->first();
                
                if ($nextSchedule) {
                    echo "  Applying overpayment to schedule {$nextSchedule->id}...\n";
                    DB::table('loan_schedules')
                        ->where('id', $nextSchedule->id)
                        ->increment('paid', $overpayment);
                    echo "  ✓ Overpayment applied\n";
                    
                    // Check if next schedule is now paid
                    $nextPaid = floatval($nextSchedule->paid ?? 0) + $overpayment;
                    if ($nextPaid >= floatval($nextSchedule->payment)) {
                        DB::table('loan_schedules')
                            ->where('id', $nextSchedule->id)
                            ->update([
                                'status' => 1,
                                'date_cleared' => now()
                            ]);
                        echo "  ✓ Next schedule also marked as PAID\n";
                    }
                }
            }
        } else {
            echo "  ⚠️  Schedule not fully paid yet (still needs " . number_format($scheduleDue - $newPaid, 2) . " UGX)\n";
        }
    }
    echo "\n";
    
    // Step 4: Check if all schedules are paid and close loan
    echo "Step 4: Checking if loan should be closed...\n";
    $allSchedules = DB::table('loan_schedules')
        ->where('loan_id', $loanId)
        ->get();
    
    $allPaid = true;
    foreach ($allSchedules as $sch) {
        $paid = floatval($sch->paid ?? 0);
        $due = floatval($sch->payment);
        
        if ($paid < $due) {
            $allPaid = false;
            echo "  Schedule {$sch->id}: " . number_format($paid, 2) . "/" . number_format($due, 2) . " - PENDING\n";
        } else {
            echo "  Schedule {$sch->id}: " . number_format($paid, 2) . "/" . number_format($due, 2) . " - PAID ✓\n";
        }
    }
    echo "\n";
    
    if ($allPaid) {
        echo "  All schedules paid! Closing loan...\n";
        DB::table('personal_loans')
            ->where('id', $loanId)
            ->update([
                'status' => 3,
                'date_closed' => now()
            ]);
        echo "  ✓ Loan closed (Status = 3)\n";
    } else {
        echo "  Loan still has pending schedules\n";
    }
    
    DB::commit();
    
    echo "\n✅ SUCCESS! Isaac's payment has been approved and processed!\n";
    echo "\nSummary:\n";
    echo "  - Repayment 947: APPROVED\n";
    echo "  - Schedule 2976: " . ($allPaid ? "PAID" : "UPDATED") . "\n";
    echo "  - Loan 133: " . ($allPaid ? "CLOSED" : "ACTIVE") . "\n";
    echo "\nView at: http://localhost:84/admin/loans/repayments/schedules/133\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Complete ===\n";
