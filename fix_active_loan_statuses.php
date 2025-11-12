<?php
/**
 * Fix active loan statuses - Update loans with schedules to status=2 (Active/Disbursed)
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Fixing active loan statuses...\n";
echo "========================================\n\n";

// Get all loan IDs that have schedules (these should be active)
$loansWithSchedules = DB::table('loan_schedules')
    ->select('loan_id')
    ->distinct()
    ->pluck('loan_id')
    ->toArray();

echo "Found " . count($loansWithSchedules) . " loans with repayment schedules\n\n";

// Check current status of these loans
$statusCounts = DB::table('personal_loans')
    ->whereIn('id', $loansWithSchedules)
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "Current status breakdown:\n";
foreach ($statusCounts as $stat) {
    $statusName = [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Active/Disbursed',
        3 => 'Completed',
        4 => 'Rejected'
    ][$stat->status] ?? 'Unknown';
    echo "  Status {$stat->status} ({$statusName}): {$stat->count} loans\n";
}

echo "\n";
echo "Loans that need fixing (have schedules but not marked as active):\n";

// Count loans with unpaid schedules but not marked as active (status != 2)
$loansToFix = DB::table('personal_loans')
    ->whereIn('id', function($query) {
        $query->select('loan_id')
              ->from('loan_schedules')
              ->where('status', 0) // Unpaid schedules
              ->distinct();
    })
    ->where('status', '!=', 2)
    ->where('status', '!=', 3) // Don't change completed loans
    ->get(['id', 'code', 'status']);

echo "  " . $loansToFix->count() . " loans need to be updated to status=2 (Active)\n\n";

if ($loansToFix->isEmpty()) {
    echo "✅ All loans are already correctly marked!\n";
    exit(0);
}

echo "Proceed with fixing? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Cancelled.\n";
    exit(0);
}

echo "\nUpdating loan statuses...\n";
echo "========================================\n";

DB::beginTransaction();

try {
    $updated = 0;
    
    foreach ($loansToFix as $loan) {
        // Check if loan has unpaid schedules
        $hasUnpaidSchedules = DB::table('loan_schedules')
            ->where('loan_id', $loan->id)
            ->where('status', 0)
            ->exists();
        
        if ($hasUnpaidSchedules) {
            DB::table('personal_loans')
                ->where('id', $loan->id)
                ->update(['status' => 2]);
            
            echo "✓ Updated loan {$loan->code} (ID: {$loan->id}) from status {$loan->status} to 2\n";
            $updated++;
        }
    }
    
    DB::commit();
    
    echo "\n========================================\n";
    echo "✅ Successfully updated {$updated} loans to Active status!\n";
    echo "========================================\n\n";
    
    echo "Verification:\n";
    $activeCount = DB::table('personal_loans')->where('status', 2)->count();
    echo "  Total active loans now: {$activeCount}\n";
    
    echo "\nView active loans at:\n";
    echo "  http://localhost:84/admin/loans/active?type=personal\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
