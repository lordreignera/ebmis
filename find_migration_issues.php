<?php

// Find all loans with migration issues - payments in repayments table but not in schedules
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FINDING LOANS WITH MIGRATION ISSUES ===\n\n";

// Get all personal loans
$loans = \App\Models\PersonalLoan::whereIn('status', [1, 2, 3])->get(); // Active, Pending, Closed

echo "Checking " . $loans->count() . " loans...\n\n";

$affectedLoans = [];

foreach ($loans as $loan) {
    // Get completed repayments
    $completedRepayments = \App\Models\Repayment::where('loan_id', $loan->id)
        ->where('status', 1)
        ->sum('amount');
    
    // Get paid amount from schedules
    $schedulePaid = \App\Models\LoanSchedule::where('loan_id', $loan->id)
        ->sum('paid');
    
    // If there's a mismatch (repayments > 0 but schedules show 0 or less)
    if ($completedRepayments > 0 && $schedulePaid < $completedRepayments) {
        $mismatch = $completedRepayments - $schedulePaid;
        
        // Only include if mismatch is significant (more than 1000 UGX)
        if ($mismatch > 1000) {
            $member = $loan->member;
            $affectedLoans[] = [
                'loan_id' => $loan->id,
                'code' => $loan->code,
                'member_name' => $member ? $member->name : 'Unknown',
                'principal' => $loan->principal,
                'status' => $loan->status,
                'completed_repayments' => $completedRepayments,
                'schedule_paid' => $schedulePaid,
                'mismatch' => $mismatch
            ];
        }
    }
}

echo "Found " . count($affectedLoans) . " loans with migration issues\n\n";

if (count($affectedLoans) > 0) {
    echo str_repeat("=", 140) . "\n";
    printf("%-8s %-20s %-30s %-15s %-15s %-15s %-15s %-10s\n", 
        "Loan ID", "Loan Code", "Member Name", "Principal", "Repayments", "In Schedules", "Mismatch", "Status");
    echo str_repeat("=", 140) . "\n";
    
    $totalMismatch = 0;
    foreach ($affectedLoans as $loan) {
        printf("%-8s %-20s %-30s %-15s %-15s %-15s %-15s %-10s\n",
            $loan['loan_id'],
            $loan['code'],
            substr($loan['member_name'], 0, 30),
            number_format($loan['principal'], 0),
            number_format($loan['completed_repayments'], 0),
            number_format($loan['schedule_paid'], 0),
            number_format($loan['mismatch'], 0),
            $loan['status'] == 1 ? 'Active' : ($loan['status'] == 2 ? 'Pending' : 'Closed')
        );
        $totalMismatch += $loan['mismatch'];
    }
    
    echo str_repeat("=", 140) . "\n";
    echo "\nTotal missing payments across all loans: UGX " . number_format($totalMismatch, 0) . "\n";
}

// Now check for Ojulong Robert specifically
echo "\n\n=== CHECKING OJULONG ROBERT (PLOAN1749827324) ===\n\n";

$robertLoan = \App\Models\PersonalLoan::where('code', 'PLOAN1749827324')->first();

if ($robertLoan) {
    echo "Loan ID: " . $robertLoan->id . "\n";
    echo "Member: " . ($robertLoan->member ? $robertLoan->member->name : 'Unknown') . "\n";
    echo "Principal: UGX " . number_format($robertLoan->principal, 0) . "\n";
    echo "Status: " . $robertLoan->status . "\n\n";
    
    $schedules = \App\Models\LoanSchedule::where('loan_id', $robertLoan->id)
        ->orderBy('payment_date')
        ->get();
    
    echo "Total Schedules: " . $schedules->count() . "\n";
    echo "Paid Schedules: " . $schedules->where('status', 1)->count() . "\n";
    echo "Unpaid Schedules: " . $schedules->where('status', 0)->count() . "\n\n";
    
    // Show schedule details
    echo str_repeat("=", 120) . "\n";
    printf("%-5s %-12s %-15s %-15s %-15s %-10s\n", 
        "ID", "Date", "Payment", "Paid", "Remaining", "Status");
    echo str_repeat("=", 120) . "\n";
    
    foreach ($schedules as $schedule) {
        $remaining = $schedule->payment - $schedule->paid;
        printf("%-5s %-12s %-15s %-15s %-15s %-10s\n",
            $schedule->id,
            date('Y-m-d', strtotime($schedule->payment_date)),
            number_format($schedule->payment, 0),
            number_format($schedule->paid, 0),
            number_format($remaining, 0),
            $schedule->status == 1 ? 'Paid' : 'Unpaid'
        );
    }
    echo str_repeat("=", 120) . "\n";
    
    // Check repayments
    $completedReps = \App\Models\Repayment::where('loan_id', $robertLoan->id)
        ->where('status', 1)
        ->get();
    
    echo "\nCompleted Repayments: " . $completedReps->count() . "\n";
    echo "Total Completed Amount: UGX " . number_format($completedReps->sum('amount'), 0) . "\n";
    echo "Total in Schedules: UGX " . number_format($schedules->sum('paid'), 0) . "\n";
    echo "Mismatch: UGX " . number_format($completedReps->sum('amount') - $schedules->sum('paid'), 0) . "\n";
    
} else {
    echo "Loan PLOAN1749827324 not found!\n";
}
