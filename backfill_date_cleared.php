<?php
/**
 * Backfill Script: Set date_cleared for Existing Paid Schedules
 * 
 * This script finds all schedules that are marked as paid (status=1)
 * but don't have date_cleared set, and sets date_cleared to the last
 * payment date to freeze late fee calculations.
 * 
 * CRITICAL: This prevents late fees from continuing to grow on already-paid schedules
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   BACKFILL: Set date_cleared for Paid Schedules               ║\n";
echo "║   Date: " . date('Y-m-d H:i:s') . "                                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Find all paid schedules without date_cleared
$paidSchedules = DB::table('loan_schedules')
    ->where('status', 1) // Paid
    ->whereNull('date_cleared')
    ->orderBy('loan_id')
    ->orderBy('id')
    ->get();

$totalCount = $paidSchedules->count();

if ($totalCount == 0) {
    echo "✓ No schedules need backfilling. All paid schedules already have date_cleared set!\n\n";
    exit(0);
}

echo "Found {$totalCount} paid schedule(s) missing date_cleared.\n\n";

// Show first 10 examples
echo "Examples (showing first 10):\n";
echo "───────────────────────────────────────────────────────────────\n";

$examples = $paidSchedules->take(10);
foreach ($examples as $schedule) {
    echo "Schedule #{$schedule->id} | Loan {$schedule->loan_id} | ";
    echo "Paid: " . number_format($schedule->paid ?? 0) . " / " . number_format($schedule->payment) . "\n";
}

if ($totalCount > 10) {
    echo "... and " . ($totalCount - 10) . " more.\n";
}

echo "\n";
echo "⚠️  WARNING: This will set date_cleared for all {$totalCount} schedules.\n";
echo "This action will FREEZE late fee calculations at the last payment date.\n\n";
echo "Do you want to proceed? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "\n❌ Backfill cancelled.\n\n";
    exit(0);
}

echo "\nProcessing schedules...\n\n";

DB::beginTransaction();

$updatedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($paidSchedules as $schedule) {
    try {
        // Find the last confirmed payment date for this schedule
        $lastPayment = DB::table('repayments')
            ->where('schedule_id', $schedule->id)
            ->where('status', 1) // Confirmed
            ->orderBy('id', 'desc')
            ->first();
        
        // ONLY set date_cleared if we have an actual payment record
        if ($lastPayment) {
            // Use last payment date
            $dateCleared = $lastPayment->date_created ?? $lastPayment->created_at ?? now();
            
            // Update the schedule
            DB::table('loan_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'date_cleared' => $dateCleared
                ]);
            
            $updatedCount++;
        } else {
            // No payment record found - skip setting date_cleared
            echo "  ⚠️  Schedule #{$schedule->id}: No payment record found, skipping date_cleared\n";
            $skippedCount++;
        }
        
        // Show progress every 50 records
        if (($updatedCount + $skippedCount) % 50 == 0) {
            echo "  → Processed " . ($updatedCount + $skippedCount) . " / {$totalCount} schedules...\n";
        }
        
    } catch (\Exception $e) {
        $errorCount++;
        echo "  ❌ Error processing schedule #{$schedule->id}: " . $e->getMessage() . "\n";
    }
}

DB::commit();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   BACKFILL COMPLETE                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Results:\n";
echo "  ✓ Updated: {$updatedCount} schedules\n";
echo "  ⚠ Skipped: {$skippedCount} schedules\n";
echo "  ❌ Errors:  {$errorCount} schedules\n\n";

if ($updatedCount > 0) {
    echo "Success! Late fees will now freeze at payment date for these {$updatedCount} schedules.\n\n";
    
    // Verify the fix
    $stillMissing = DB::table('loan_schedules')
        ->where('status', 1)
        ->whereNull('date_cleared')
        ->count();
    
    if ($stillMissing == 0) {
        echo "✓ VERIFICATION PASSED: All paid schedules now have date_cleared set!\n\n";
    } else {
        echo "⚠️  WARNING: {$stillMissing} paid schedule(s) still missing date_cleared.\n";
        echo "   You may need to run this script again or investigate manually.\n\n";
    }
}

echo "Recommendation: Monitor late fee calculations over the next few days\n";
echo "to ensure they're not continuing to grow on paid schedules.\n\n";

echo "✓ Backfill complete!\n\n";
