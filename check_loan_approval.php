<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;

echo "=== CHECK LOAN 132 STATUS ===\n\n";

$loan = PersonalLoan::with(['member', 'product'])->find(132);

if ($loan) {
    echo "Loan ID: {$loan->id}\n";
    echo "Code: {$loan->code}\n";
    echo "Member: {$loan->member->fname} {$loan->member->lname}\n";
    echo "Status: {$loan->status}\n";
    echo "Status meaning: ";
    switch($loan->status) {
        case 0: echo "Pending Approval\n"; break;
        case 1: echo "Approved (Ready for Disbursement)\n"; break;
        case 2: echo "Disbursed\n"; break;
        case 3: echo "Completed\n"; break;
        case 4: echo "Rejected\n"; break;
        default: echo "Unknown\n";
    }
    echo "Verified: {$loan->verified}\n";
    echo "Charge Type: {$loan->charge_type}\n";
    echo "Date Approved: " . ($loan->date_approved ?? 'Not set') . "\n";
    echo "Approved By: " . ($loan->approved_by ?? 'Not set') . "\n";
    
    echo "\n\n=== DISBURSEMENT CHECK ===\n";
    echo "Has disbursements: " . $loan->disbursements()->count() . "\n";
    
    if ($loan->status == 1) {
        echo "\n✅ Loan is approved and should appear in disbursements page\n";
        echo "Since charge_type = {$loan->charge_type}, it should be ";
        echo ($loan->charge_type == 1) ? "ALWAYS READY (charges deducted)\n" : "READY ONLY IF FEES PAID\n";
    } else {
        echo "\n❌ Loan status is {$loan->status}, not approved (1)\n";
    }
} else {
    echo "Loan 132 not found!\n";
}
