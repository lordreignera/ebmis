<?php
/**
 * Fix loan PDLOAN2511120004001 - Complete disbursement and update loan status
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PersonalLoan;
use App\Models\Disbursement;

echo "Fixing loan PDLOAN2511120004001...\n\n";

// Find the loan by code
$loan = PersonalLoan::where('code', 'PDLOAN2511120004001')->first();

if (!$loan) {
    echo "❌ Loan not found!\n";
    exit(1);
}

echo "✓ Found loan ID: {$loan->id}\n";
echo "  Current status: {$loan->status} (should be 2 for disbursed)\n";

// Find the disbursement record
$disbursement = Disbursement::where('loan_id', $loan->id)
                           ->where('loan_type', 1)
                           ->orderBy('created_at', 'desc')
                           ->first();

if (!$disbursement) {
    echo "❌ No disbursement record found!\n";
    exit(1);
}

echo "✓ Found disbursement ID: {$disbursement->id}\n";
echo "  Current status: {$disbursement->status} (0=Pending, 1=Processing, 2=Completed)\n";
echo "  Amount: UGX " . number_format($disbursement->amount, 0) . "\n\n";

// Check loan schedules
$scheduleCount = DB::table('loan_schedules')
                   ->where('loan_id', $loan->id)
                   ->count();

echo "  Loan schedules: {$scheduleCount}\n\n";

echo "Applying fixes...\n";
echo "----------------------------------------\n";

DB::beginTransaction();

try {
    // 1. Update disbursement status to completed
    if ($disbursement->status != 2) {
        $disbursement->update(['status' => 2]);
        echo "✓ Disbursement status updated to 2 (Completed)\n";
    } else {
        echo "✓ Disbursement already marked as completed\n";
    }

    // 2. Update loan status to disbursed/active
    if ($loan->status != 2) {
        $loan->update(['status' => 2]);
        echo "✓ Loan status updated to 2 (Disbursed/Active)\n";
    } else {
        echo "✓ Loan already marked as disbursed\n";
    }

    // 3. Ensure loan schedules exist
    if ($scheduleCount == 0) {
        echo "⚠ No loan schedules found. You may need to generate them manually.\n";
        echo "  Run: php artisan tinker\n";
        echo "  Then: App\\Models\\PersonalLoan::find({$loan->id})->generateSchedules()\n";
    } else {
        echo "✓ Loan has {$scheduleCount} repayment schedules\n";
    }

    DB::commit();

    echo "\n========================================\n";
    echo "✅ Loan successfully fixed!\n";
    echo "========================================\n\n";

    echo "Loan Details:\n";
    echo "  Code: {$loan->code}\n";
    echo "  Status: {$loan->status} (2 = Active/Disbursed)\n";
    echo "  Principal: UGX " . number_format($loan->principal, 0) . "\n";
    echo "  Period: {$loan->period} installments\n";
    echo "  Schedules: {$scheduleCount}\n\n";

    echo "The loan should now appear in:\n";
    echo "  ✓ Active Loans: http://localhost:84/admin/loans/active?type=personal\n";
    echo "  ✓ No longer in: http://localhost:84/admin/loans/disbursements/pending\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
