<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;

$member = Member::where('fname', 'Norah')->where('lname', 'Nakamatte')->first();

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
    
    foreach ($activeLoans as $loan) {
        echo "Loan ID: " . $loan->id . "\n";
        echo "  Code: " . $loan->code . "\n";
        echo "  Status: " . $loan->status . " (" . 
             ($loan->status == 0 ? 'Pending' : 
             ($loan->status == 1 ? 'Approved' : 
             ($loan->status == 2 ? 'Disbursed' : 
             ($loan->status == 3 ? 'Completed' : 'Unknown')))) . ")\n";
        
        $unpaidSchedules = $loan->schedules()->where('status', 0)->count();
        echo "  Unpaid Schedules: " . $unpaidSchedules . "\n";
        echo "\n";
    }
} else {
    echo "Member 'Norah Nakamatte' not found\n";
}
