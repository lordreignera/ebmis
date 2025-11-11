<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\GroupLoan;

echo "=== DISBURSEMENTS PAGE DATA ===\n\n";

// Personal loans query - same as in DisbursementController
$personalLoansQuery = PersonalLoan::where('status', 1) // Approved loans
    ->whereDoesntHave('disbursements', function($q) {
        $q->where('status', 1); // No successful disbursement yet
    });

$personalLoans = $personalLoansQuery->with(['member', 'branch', 'product', 'product.charges'])->get()->map(function($loan) {
    $loan->loan_type = 'personal';
    return $loan;
});

echo "Personal Loans Ready for Disbursement: " . $personalLoans->count() . "\n\n";

foreach ($personalLoans as $loan) {
    echo "Loan ID: {$loan->id}\n";
    echo "Code: {$loan->code}\n";
    echo "Member: {$loan->member->fname} {$loan->member->lname}\n";
    echo "Principal: " . number_format($loan->principal) . "\n";
    echo "Charge Type: {$loan->charge_type}\n";
    echo "Status: {$loan->status}\n";
    echo "---\n";
}

// Check if loan 132 is in the result
$loan132 = $personalLoans->where('id', 132)->first();
if ($loan132) {
    echo "\n✅ Loan 132 IS in the disbursements query result\n";
} else {
    echo "\n❌ Loan 132 NOT in disbursements query result\n";
    echo "Checking why...\n\n";
    
    $loan = PersonalLoan::find(132);
    echo "Status: {$loan->status} (should be 1)\n";
    echo "Has successful disbursements: " . $loan->disbursements()->where('status', 1)->count() . "\n";
}
