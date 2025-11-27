<?php

/**
 * Waive ONLY Late Fees that ACCUMULATED During the 4-Week Upgrade Period
 * 
 * IMPORTANT LOGIC:
 * - If a schedule was due BEFORE upgrade period: Keep old fees, waive only fees from upgrade days
 * - If a schedule was due DURING upgrade period: Waive all fees
 * - If a schedule was due AFTER upgrade period: Keep all fees (not affected)
 * 
 * Example:
 * - Schedule due: Nov 1 (before upgrade started Oct 30)
 * - Already had 10 days late by Oct 30 = some late fees
 * - During Oct 30 - Nov 27: added 28 more days
 * - We waive ONLY the fees from those 28 days, keep the original fees
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   WAIVE LATE FEES - ACCUMULATED DURING UPGRADE ONLY\n";
echo "   Proportional Waiver Based on Days in Upgrade Period\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

// Define upgrade period
$upgradeStart = Carbon::parse('2025-10-30'); // 4 weeks ago
$upgradeEnd = Carbon::parse('2025-11-27');   // Today
$upgradeDays = $upgradeStart->diffInDays($upgradeEnd) + 1; // Include both dates

echo "System Upgrade Period:\n";
echo "  Start: " . $upgradeStart->format('d-m-Y (l)') . "\n";
echo "  End:   " . $upgradeEnd->format('d-m-Y (l)') . "\n";
echo "  Duration: {$upgradeDays} days\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "LOGIC: Calculate proportional late fee waiver\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

echo "For each overdue schedule:\n";
echo "  1. Check when it was due\n";
echo "  2. Calculate how many days it was overdue DURING upgrade period\n";
echo "  3. Calculate late fee for ONLY those upgrade days\n";
echo "  4. Waive that specific amount\n\n";

echo "Examples:\n";
echo "  Schedule due Oct 20 (10 days before upgrade):\n";
echo "    - Was already 10 days late when upgrade started\n";
echo "    - Added 28 days during upgrade = 38 days total\n";
echo "    - Waive: Late fee for 28 days only (keep 10 days fee)\n\n";
echo "  Schedule due Nov 10 (during upgrade):\n";
echo "    - Became overdue during upgrade period\n";
echo "    - All late days are upgrade days\n";
echo "    - Waive: ALL late fees\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 1: Finding all overdue schedules...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$overdueSchedules = DB::table('loan_schedules as ls')
    ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->leftJoin('late_fees as lf', function($join) {
        $join->on('ls.id', '=', 'lf.schedule_id')
             ->where('lf.status', '=', 0); // Pending only
    })
    ->where('ls.status', 0) // Not paid
    ->where('ls.payment_date', '<', $upgradeEnd->format('Y-m-d'))
    ->select(
        'ls.id as schedule_id',
        'ls.loan_id',
        'ls.payment_date as due_date',
        'ls.principal',
        'ls.interest',
        'pl.code as loan_code',
        'pl.period as period_type',
        'm.id as member_id',
        'm.fname',
        'm.lname',
        'lf.id as late_fee_id',
        'lf.amount as current_late_fee'
    )
    ->get();

echo "Found " . $overdueSchedules->count() . " overdue schedules\n\n";

$waiversToApply = [];
$totalWaiverAmount = 0;

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 2: Calculating proportional waivers...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

foreach ($overdueSchedules as $schedule) {
    $dueDate = Carbon::parse($schedule->due_date);
    $periodType = $schedule->period_type ?? '2';
    
    // Calculate total days overdue as of upgrade end
    $totalDaysOverdue = $dueDate->diffInDays($upgradeEnd);
    
    if ($totalDaysOverdue <= 0) {
        continue; // Not actually overdue
    }
    
    // Calculate how many of those days fell DURING the upgrade period
    $daysInUpgrade = 0;
    
    if ($dueDate->lessThan($upgradeStart)) {
        // Schedule was due BEFORE upgrade started
        // Count days from upgrade start to upgrade end
        $daysInUpgrade = $upgradeStart->diffInDays($upgradeEnd) + 1;
    } elseif ($dueDate->between($upgradeStart, $upgradeEnd)) {
        // Schedule was due DURING upgrade period
        // Count days from due date to upgrade end
        $daysInUpgrade = $dueDate->diffInDays($upgradeEnd) + 1;
    } else {
        // Schedule due AFTER upgrade - not affected
        $daysInUpgrade = 0;
    }
    
    if ($daysInUpgrade <= 0) {
        continue; // No days during upgrade
    }
    
    // Calculate late fee per day
    $scheduleAmount = $schedule->principal + $schedule->interest;
    $lateFeeRate = 0.06; // 6% per period
    
    // Calculate periods for upgrade days only
    $upgradePeriodsOverdue = 0;
    if ($periodType == '1') {
        // Weekly
        $upgradePeriodsOverdue = ceil($daysInUpgrade / 7);
    } elseif ($periodType == '2') {
        // Monthly
        $upgradePeriodsOverdue = ceil($daysInUpgrade / 30);
    } elseif ($periodType == '3') {
        // Daily
        $upgradePeriodsOverdue = $daysInUpgrade;
    }
    
    $waiverAmount = ($scheduleAmount * $lateFeeRate) * $upgradePeriodsOverdue;
    
    if ($waiverAmount > 0) {
        $waiversToApply[] = [
            'schedule_id' => $schedule->schedule_id,
            'loan_id' => $schedule->loan_id,
            'member_id' => $schedule->member_id,
            'member_name' => trim("{$schedule->fname} {$schedule->lname}"),
            'loan_code' => $schedule->loan_code,
            'due_date' => $schedule->due_date,
            'total_days_overdue' => $totalDaysOverdue,
            'days_in_upgrade' => $daysInUpgrade,
            'upgrade_periods' => $upgradePeriodsOverdue,
            'current_late_fee' => $schedule->current_late_fee ?? 0,
            'waiver_amount' => $waiverAmount,
            'late_fee_id' => $schedule->late_fee_id,
            'period_type' => $periodType
        ];
        
        $totalWaiverAmount += $waiverAmount;
    }
}

echo "Schedules with upgrade period late fees: " . count($waiversToApply) . "\n";
echo "Total waiver amount: " . number_format($totalWaiverAmount, 0) . " UGX\n\n";

if (count($waiversToApply) == 0) {
    echo "✓ No late fees to waive.\n\n";
    exit(0);
}

echo "─────────────────────────────────────────────────────────────────────\n";
echo "SAMPLE CALCULATIONS (First 10):\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

foreach (array_slice($waiversToApply, 0, 10) as $idx => $waiver) {
    echo ($idx + 1) . ". {$waiver['member_name']}\n";
    echo "   Loan: {$waiver['loan_code']}\n";
    echo "   Due Date: " . Carbon::parse($waiver['due_date'])->format('d-m-Y') . "\n";
    echo "   Total Days Overdue: {$waiver['total_days_overdue']} days\n";
    echo "   Days During Upgrade: {$waiver['days_in_upgrade']} days\n";
    echo "   Upgrade Periods: {$waiver['upgrade_periods']}\n";
    echo "   Waiver Amount: " . number_format($waiver['waiver_amount'], 0) . " UGX\n\n";
}

if (count($waiversToApply) > 10) {
    echo "... and " . (count($waiversToApply) - 10) . " more\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   WAIVER SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Schedules to waive: " . count($waiversToApply) . "\n";
echo "Total waiver amount: " . number_format($totalWaiverAmount, 0) . " UGX\n";
echo "Affected members: " . count(array_unique(array_column($waiversToApply, 'member_id'))) . "\n\n";

echo "Method: Create/update late_fees table with waived amounts\n";
echo "Reason: 'Late fees accumulated during system upgrade period (Oct 30 - Nov 27)'\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "Do you want to proceed? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n✗ Operation cancelled.\n\n";
    exit(0);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   PROCESSING WAIVERS\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {
    $created = 0;
    $updated = 0;
    $waiver_reason = 'Late fees accumulated during system upgrade period (Oct 30 - Nov 27, 2025)';
    
    foreach ($waiversToApply as $waiver) {
        if ($waiver['late_fee_id']) {
            // Update existing late_fee record - mark as waived
            DB::table('late_fees')
                ->where('id', $waiver['late_fee_id'])
                ->update([
                    'status' => 2, // Waived
                    'waiver_reason' => $waiver_reason,
                    'waived_at' => now(),
                    'waived_by' => null,
                    'updated_at' => now()
                ]);
            $updated++;
        } else {
            // Create new late_fee record as waived
            DB::table('late_fees')->insert([
                'loan_id' => $waiver['loan_id'],
                'schedule_id' => $waiver['schedule_id'],
                'member_id' => $waiver['member_id'],
                'amount' => $waiver['waiver_amount'],
                'days_overdue' => $waiver['days_in_upgrade'],
                'periods_overdue' => $waiver['upgrade_periods'],
                'period_type' => $waiver['period_type'],
                'schedule_due_date' => $waiver['due_date'],
                'calculated_date' => now(),
                'status' => 2, // Waived
                'waiver_reason' => $waiver_reason,
                'waived_at' => now(),
                'waived_by' => null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $created++;
        }
        
        if (($created + $updated) % 50 == 0) {
            echo "  ... processed " . ($created + $updated) . " records\n";
        }
    }
    
    echo "  ✓ Updated {$updated} existing records\n";
    echo "  ✓ Created {$created} new records\n\n";
    
    // Save audit trail
    $auditFile = 'late_fees_proportional_waiver_' . date('Y-m-d_His') . '.json';
    $auditData = [
        'action' => 'proportional_late_fee_waiver',
        'timestamp' => now()->toDateTimeString(),
        'upgrade_period' => [
            'start' => $upgradeStart->format('Y-m-d'),
            'end' => $upgradeEnd->format('Y-m-d'),
            'days' => $upgradeDays
        ],
        'summary' => [
            'schedules_affected' => count($waiversToApply),
            'total_waived' => $totalWaiverAmount,
            'records_updated' => $updated,
            'records_created' => $created
        ],
        'details' => $waiversToApply
    ];
    
    file_put_contents($auditFile, json_encode($auditData, JSON_PRETTY_PRINT));
    echo "  ✓ Audit trail: {$auditFile}\n\n";
    
    DB::commit();
    
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✓ SUCCESS\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Results:\n";
    echo "  Total waived: " . number_format($totalWaiverAmount, 0) . " UGX\n";
    echo "  Schedules affected: " . count($waiversToApply) . "\n";
    echo "  Updated: {$updated} | Created: {$created}\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
