<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\LoanSchedule;
use App\Models\Repayment;
use App\Models\Guarantor;

// Get loan code from command line argument or use default
$loanCode = $argv[1] ?? 'PLOAN1760370567';

echo "========================================\n";
echo "LOAN DELETION SCRIPT\n";
echo "========================================\n\n";

$loan = PersonalLoan::where('code', $loanCode)->first();

if (!$loan) {
    echo "❌ Loan not found: $loanCode\n";
    exit(1);
}

echo "✓ Found loan: {$loan->code}\n";
echo "  Member: {$loan->member->fname} {$loan->member->lname}\n";
echo "  Principal: {$loan->principal}\n";
echo "  Status: {$loan->status}\n";
echo "  Date Created: {$loan->datecreated}\n\n";

echo "⚠️  WARNING: This will permanently delete the loan and all related records!\n";
echo "Do you want to continue? (yes/no): ";

// For production use, require confirmation
if (php_sapi_name() === 'cli') {
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim(strtolower($line)) !== 'yes') {
        echo "\n❌ Deletion cancelled.\n";
        exit(0);
    }
    fclose($handle);
}

echo "\n🗑️  Deleting related records...\n";

// Delete loan schedules
$schedulesCount = LoanSchedule::where('loan_id', $loan->id)->count();
LoanSchedule::where('loan_id', $loan->id)->delete();
echo "  ✓ Deleted {$schedulesCount} loan schedules\n";

// Delete repayments
$repaymentsCount = Repayment::where('loan_id', $loan->id)->count();
Repayment::where('loan_id', $loan->id)->delete();
echo "  ✓ Deleted {$repaymentsCount} repayments\n";

// Delete guarantors
$guarantorsCount = Guarantor::where('loan_id', $loan->id)->count();
Guarantor::where('loan_id', $loan->id)->delete();
echo "  ✓ Deleted {$guarantorsCount} guarantors\n";

// Delete the loan
$loan->delete();
echo "\n✅ Loan {$loanCode} deleted successfully!\n";
echo "========================================\n";
