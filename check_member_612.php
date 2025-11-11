<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;

$member = Member::find(612);

if ($member) {
    echo "Member Found:\n";
    echo "ID: " . $member->id . "\n";
    echo "Name: " . $member->fname . " " . $member->lname . "\n";
    echo "Status: " . $member->status . "\n";
    echo "Is Approved: " . ($member->isApproved() ? 'YES' : 'NO') . "\n";
    echo "\n";
    
    echo "Checking for active loans...\n";
    $hasActive = $member->hasActiveLoan();
    echo "Has Active Loan: " . ($hasActive ? 'YES' : 'NO') . "\n";
    echo "\n";
    
    // Get all loans
    $allLoans = $member->loans()->get();
    echo "Total Loans: " . $allLoans->count() . "\n";
    
    // Check approved/disbursed loans
    $activeLoans = $member->loans()->whereIn('status', [1, 2])->get();
    echo "Approved/Disbursed Loans: " . $activeLoans->count() . "\n";
    echo "\n";
    
    if ($activeLoans->count() > 0) {
        foreach ($activeLoans as $loan) {
            echo "Loan ID: " . $loan->id . "\n";
            echo "  Code: " . $loan->code . "\n";
            echo "  Principal: " . $loan->principal . "\n";
            echo "  Status: " . $loan->status . " (" . 
                 ($loan->status == 0 ? 'Pending' : 
                 ($loan->status == 1 ? 'Approved' : 
                 ($loan->status == 2 ? 'Disbursed' : 
                 ($loan->status == 3 ? 'Completed' : 'Unknown')))) . ")\n";
            
            $totalSchedules = $loan->schedules()->count();
            $unpaidSchedules = $loan->schedules()->where('status', 0)->count();
            echo "  Total Schedules: " . $totalSchedules . "\n";
            echo "  Unpaid Schedules: " . $unpaidSchedules . "\n";
            echo "\n";
        }
    }
    
    // Check pending loans too
    $pendingLoans = $member->loans()->where('status', 0)->get();
    if ($pendingLoans->count() > 0) {
        echo "Pending Loans: " . $pendingLoans->count() . "\n";
        foreach ($pendingLoans as $loan) {
            echo "  Loan ID: " . $loan->id . " - Code: " . $loan->code . " - Principal: " . $loan->principal . "\n";
        }
    }
}
