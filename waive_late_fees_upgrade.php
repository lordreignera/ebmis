<?php

/**
 * Waive Late Fees from System Upgrade Period
 * 
 * This script identifies and waives late fees that accumulated during
 * the 4-week system upgrade period when clients couldn't make payments.
 * 
 * Works with the new late_fees table for proper tracking.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\LateFee;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   WAIVE LATE FEES - SYSTEM UPGRADE PERIOD\n";
echo "   For Schedules Due During the 4-Week Maintenance\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

// Define upgrade period - 4 weeks ago from today
$fourWeeksAgo = Carbon::now()->subWeeks(4);
$today = Carbon::now();

echo "System Upgrade Period:\n";
echo "  Start: " . $fourWeeksAgo->format('d-m-Y (l)') . "\n";
echo "  End:   " . $today->format('d-m-Y (l)') . "\n";
echo "  Duration: 4 weeks (28 days)\n\n";

echo "Reason: System maintenance prevented loan repayments\n";
echo "Action: Waive late fees for schedules due during this period\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 1: Finding late fees to waive...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

// Find all late fees for schedules that were DUE during the upgrade period
$lateFeesToWaive = DB::table('late_fees as lf')
    ->join('loan_schedules as ls', 'lf.schedule_id', '=', 'ls.id')
    ->join('members as m', 'lf.member_id', '=', 'm.id')
    ->join('personal_loans as pl', 'lf.loan_id', '=', 'pl.id')
    ->where('lf.status', 0) // Pending only
    ->whereBetween('lf.schedule_due_date', [
        $fourWeeksAgo->format('Y-m-d'),
        $today->format('Y-m-d')
    ])
    ->select(
        'lf.*',
        'm.fname',
        'm.lname',
        'pl.code as loan_code',
        'ls.payment_date'
    )
    ->get();

echo "Late fees found: " . $lateFeesToWaive->count() . "\n";

if ($lateFeesToWaive->count() == 0) {
    echo "\n✓ No pending late fees found for the upgrade period.\n";
    echo "  Either they were already waived, or no late fees exist yet.\n\n";
    
    echo "TIP: Run 'calculate_late_fees.php' first to populate the late_fees table.\n\n";
    exit(0);
}

$totalAmount = $lateFeesToWaive->sum('amount');
$affectedLoans = $lateFeesToWaive->pluck('loan_id')->unique()->count();
$affectedMembers = $lateFeesToWaive->pluck('member_id')->unique()->count();

echo "Affected members: {$affectedMembers}\n";
echo "Affected loans: {$affectedLoans}\n";
echo "Total late fees to waive: " . number_format($totalAmount, 0) . " UGX\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "AFFECTED CLIENTS (First 10):\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$displayCount = min(10, $lateFeesToWaive->count());
foreach ($lateFeesToWaive->take(10) as $idx => $lateFee) {
    $memberName = trim("{$lateFee->fname} {$lateFee->lname}");
    $dueDate = Carbon::parse($lateFee->schedule_due_date)->format('d-m-Y');
    
    echo ($idx + 1) . ". {$memberName}\n";
    echo "   Loan: {$lateFee->loan_code}\n";
    echo "   Schedule Due: {$dueDate}\n";
    echo "   Days Overdue: {$lateFee->days_overdue}\n";
    echo "   Late Fee: " . number_format($lateFee->amount, 0) . " UGX → WAIVED\n\n";
}

if ($lateFeesToWaive->count() > 10) {
    echo "... and " . ($lateFeesToWaive->count() - 10) . " more\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   WAIVER SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Action: Waive late fees for system upgrade period\n";
echo "Period: " . $fourWeeksAgo->format('d-m-Y') . " to " . $today->format('d-m-Y') . "\n";
echo "Late fees to waive: " . $lateFeesToWaive->count() . " records\n";
echo "Total amount: " . number_format($totalAmount, 0) . " UGX\n";
echo "Affected clients: {$affectedMembers}\n\n";

echo "These late fees will be marked as WAIVED (status = 2)\n";
echo "Reason: 'System upgrade prevented timely payment'\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "Do you want to proceed with waiving these late fees? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n✗ Operation cancelled.\n\n";
    exit(0);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   PROCESSING WAIVER\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {
    $waivedCount = 0;
    $waiver_reason = 'System upgrade period - clients unable to make timely payments';
    
    echo "Waiving late fees...\n";
    
    foreach ($lateFeesToWaive as $lateFee) {
        DB::table('late_fees')
            ->where('id', $lateFee->id)
            ->update([
                'status' => 2, // Waived
                'waiver_reason' => $waiver_reason,
                'waived_at' => $today,
                'waived_by' => null, // System waiver
                'updated_at' => $today
            ]);
        
        $waivedCount++;
        
        if ($waivedCount % 50 == 0) {
            echo "  ... waived {$waivedCount} late fees\n";
        }
    }
    
    echo "  ✓ Waived {$waivedCount} late fees\n\n";
    
    // Save audit trail
    echo "Saving audit trail...\n";
    
    $auditFile = 'late_fees_waiver_' . date('Y-m-d_His') . '.json';
    $auditData = [
        'action' => 'waive_system_upgrade_late_fees',
        'timestamp' => $today->toDateTimeString(),
        'upgrade_period' => [
            'start' => $fourWeeksAgo->format('Y-m-d'),
            'end' => $today->format('Y-m-d'),
            'duration_weeks' => 4
        ],
        'summary' => [
            'late_fees_waived' => $waivedCount,
            'total_amount_waived' => $totalAmount,
            'affected_members' => $affectedMembers,
            'affected_loans' => $affectedLoans
        ],
        'waiver_reason' => $waiver_reason,
        'details' => $lateFeesToWaive->map(function($lf) {
            return [
                'late_fee_id' => $lf->id,
                'member_name' => trim("{$lf->fname} {$lf->lname}"),
                'loan_code' => $lf->loan_code,
                'schedule_due_date' => $lf->schedule_due_date,
                'days_overdue' => $lf->days_overdue,
                'amount_waived' => $lf->amount
            ];
        })->toArray()
    ];
    
    file_put_contents($auditFile, json_encode($auditData, JSON_PRETTY_PRINT));
    echo "  ✓ Audit trail saved: {$auditFile}\n\n";
    
    DB::commit();
    
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✓ SUCCESS - LATE FEES WAIVED\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Results:\n";
    echo "  Late fees waived: {$waivedCount}\n";
    echo "  Total amount: " . number_format($totalAmount, 0) . " UGX\n";
    echo "  Affected members: {$affectedMembers}\n";
    echo "  Affected loans: {$affectedLoans}\n";
    echo "  Audit file: {$auditFile}\n\n";
    
    echo "Next steps:\n";
    echo "  1. Clients can now pay original schedule amounts\n";
    echo "  2. No late fees charged for upgrade period\n";
    echo "  3. Inform clients about the waiver\n";
    echo "  4. Review waived fees in late_fees table (status = 2)\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✗ ERROR\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    
    echo "Transaction rolled back - no changes made.\n\n";
    exit(1);
}
