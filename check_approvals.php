<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\GroupLoan;

echo "=== APPROVALS PAGE DATA (Status = 0) ===\n\n";

// Get personal loans pending approval
$personalLoans = PersonalLoan::where('status', 0)
    ->with(['member', 'product', 'branch'])
    ->get()
    ->map(function($loan) {
        $loan->loan_type = 'personal';
        return $loan;
    });

echo "Personal Loans Pending Approval: " . $personalLoans->count() . "\n";
foreach ($personalLoans as $loan) {
    echo "\nLoan ID: {$loan->id}\n";
    echo "Code: {$loan->loan_code}\n";
    echo "Member: {$loan->member->fname} {$loan->member->lname}\n";
    echo "Product: {$loan->product->name}\n";
    echo "Principal: " . number_format($loan->principal) . "\n";
    echo "Created: {$loan->datecreated}\n";
    echo "---\n";
}

// Get group loans pending approval
$groupLoans = GroupLoan::where('status', 0)
    ->with(['group', 'product', 'branch'])
    ->get()
    ->map(function($loan) {
        $loan->loan_type = 'group';
        return $loan;
    });

echo "\n\nGroup Loans Pending Approval: " . $groupLoans->count() . "\n";
foreach ($groupLoans as $loan) {
    echo "\nLoan ID: {$loan->id}\n";
    echo "Code: {$loan->loan_code}\n";
    echo "Group: {$loan->group->name}\n";
    echo "Product: {$loan->product->name}\n";
    echo "Principal: " . number_format($loan->principal) . "\n";
    echo "Created: {$loan->datecreated}\n";
    echo "---\n";
}

// Check total
$total = $personalLoans->count() + $groupLoans->count();
echo "\n\nTOTAL LOANS PENDING APPROVAL: {$total}\n";

// Specifically check for our test loan
$testLoan = PersonalLoan::find(132);
if ($testLoan) {
    echo "\n=== TEST LOAN 132 ===\n";
    echo "Status: {$testLoan->status}\n";
    echo "Member: {$testLoan->member->fname} {$testLoan->member->lname}\n";
    echo "Should appear on approvals page: YES\n";
}
