<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;

echo "=== DEBUG LOAN 132 ===\n\n";

$loan = PersonalLoan::with(['member', 'product'])->find(132);

if ($loan) {
    echo "Loan ID: {$loan->id}\n";
    echo "Loan Code: {$loan->code}\n";
    echo "Member ID: {$loan->member_id}\n";
    echo "Member Name: {$loan->member->fname} {$loan->member->lname}\n";
    echo "Product: {$loan->product->name}\n";
    echo "Status: {$loan->status}\n";
    echo "Principal: {$loan->principal}\n";
    
    echo "\n\n=== What should be in the table ===\n";
    echo "Row ID attribute: {$loan->id}\n";
    echo "Approve button should call: approveLoan({$loan->id}, 'personal', '{$loan->code}')\n";
} else {
    echo "Loan 132 not found!\n";
}
