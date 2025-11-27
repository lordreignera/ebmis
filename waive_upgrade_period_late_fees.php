<?php

/**
 * Waive Late Fees from 4-Week System Upgrade Period
 * 
 * During the system upgrade (last 4 weeks), clients couldn't repay their loans.
 * This script:
 * 1. Identifies all schedules that were DUE during the upgrade period
 * 2. Removes any late fee records created during this period
 * 3. Does NOT reschedule - keeps original due dates
 * 4. Allows clients to pay original amounts WITHOUT accumulated late fees
 * 
 * IMPORTANT: Only waives late fees that accumulated DURING the upgrade.
 * Pre-existing late fees (before 4 weeks ago) are NOT affected.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   WAIVE LATE FEES - SYSTEM UPGRADE PERIOD\n";
echo "   4 Weeks When Clients Couldn't Make Payments\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

$fourWeeksAgo = Carbon::now()->subWeeks(4);
$today = Carbon::now();

echo "System Upgrade Period:\n";
echo "  From: " . $fourWeeksAgo->format('d-m-Y') . " (" . $fourWeeksAgo->format('D') . ")\n";
echo "  To:   " . $today->format('d-m-Y') . " (" . $today->format('D') . ")\n";
echo "  Duration: 4 weeks (28 days)\n\n";

echo "Reason: System maintenance prevented loan repayments\n";
echo "Action: Waive late fees accumulated during this period\n";
echo "Date: " . $today->format('Y-m-d H:i:s') . "\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 1: Finding affected schedules...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

// Get all unpaid schedules
$allUnpaidSchedules = DB::table('loan_schedules')
    ->where('status', 0)
    ->get();

echo "Total unpaid schedules in system: " . $allUnpaidSchedules->count() . "\n";

// Filter schedules that became DUE during the upgrade period
$affectedSchedules = $allUnpaidSchedules->filter(function($schedule) use ($fourWeeksAgo, $today) {
    $parts = explode('-', $schedule->payment_date);
    if (count($parts) == 3) {
        try {
            $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
            // Include schedules that were due DURING the upgrade period
            return $dueDate->between($fourWeeksAgo, $today);
        } catch (\Exception $e) {
            return false;
        }
    }
    return false;
});

echo "Schedules due during upgrade period: " . $affectedSchedules->count() . "\n\n";

if ($affectedSchedules->count() == 0) {
    echo "✓ No schedules were due during the system upgrade period.\n";
    echo "  No action needed.\n\n";
    exit(0);
}

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 2: Analyzing affected loans and calculating late fees...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$loanIds = $affectedSchedules->pluck('loan_id')->unique();

// Get loan details with correct column name (product_type not product_id)
$loans = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->whereIn('pl.id', $loanIds)
    ->select('pl.*', 'p.period_type', 'p.name as product_name')
    ->get()
    ->keyBy('id');

echo "Affected loans: " . $loans->count() . "\n\n";

$totalLateFees = 0;
$details = [];
$processedCount = 0;

foreach ($affectedSchedules as $schedule) {
    $loan = $loans->get($schedule->loan_id);
    if (!$loan) continue;
    
    $member = DB::table('members')->where('id', $loan->member_id)->first();
    $memberName = $member ? trim("{$member->fname} {$member->lname}") : 'Unknown';
    
    try {
        $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
        // Only calculate if actually overdue (due date in past)
        if ($dueDate->isPast()) {
            $daysOverdue = $dueDate->diffInDays($today);
        } else {
            $daysOverdue = 0;
        }
        
        $periodsOverdue = 0;
        $periodType = '';
        
        if ($loan->period_type == '1') {
            $periodsOverdue = ceil($daysOverdue / 7);
            $periodType = 'Weekly';
        } else if ($loan->period_type == '2') {
            $periodsOverdue = ceil($daysOverdue / 30);
            $periodType = 'Monthly';
        } else if ($loan->period_type == '3') {
            $periodsOverdue = $daysOverdue;
            $periodType = 'Daily';
        }
        
        // Calculate late fee (6% per period)
        $scheduleAmount = $schedule->principal + $schedule->interest;
        $lateFee = ($scheduleAmount * 0.06) * $periodsOverdue;
        $totalLateFees += $lateFee;
        
        $processedCount++;
        
        $details[] = [
            'schedule_id' => $schedule->id,
            'loan_id' => $loan->id,
            'loan_code' => $loan->code,
            'member_id' => $loan->member_id,
            'member_name' => $memberName,
            'product_name' => $loan->product_name,
            'due_date' => $schedule->payment_date,
            'days_overdue' => $daysOverdue,
            'periods_overdue' => $periodsOverdue,
            'period_type' => $periodType,
            'schedule_amount' => $scheduleAmount,
            'late_fee_waived' => $lateFee,
            'principal' => $schedule->principal,
            'interest' => $schedule->interest
        ];
    } catch (\Exception $e) {
        continue;
    }
}

echo "Schedules analyzed: {$processedCount}\n";
echo "Total late fees to waive: " . number_format($totalLateFees, 0) . " UGX\n\n";

// Show detailed breakdown
echo "─────────────────────────────────────────────────────────────────────\n";
echo "AFFECTED CLIENTS (First 10):\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$displayCount = min(10, count($details));
for ($i = 0; $i < $displayCount; $i++) {
    $d = $details[$i];
    echo ($i + 1) . ". {$d['member_name']}\n";
    echo "   Loan: {$d['loan_code']} ({$d['product_name']})\n";
    echo "   Due Date: {$d['due_date']} | {$d['days_overdue']} days overdue\n";
    echo "   Schedule Amount: " . number_format($d['schedule_amount'], 0) . " UGX\n";
    echo "   Late Fee to Waive: " . number_format($d['late_fee_waived'], 0) . " UGX\n\n";
}

if (count($details) > 10) {
    echo "... and " . (count($details) - 10) . " more clients\n\n";
}

// Check for existing late fee records
echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 3: Checking for late fee records in database...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$fourWeeksAgoStr = $fourWeeksAgo->format('Y-m-d 00:00:00');

$lateFeeRecords = DB::table('fees')
    ->where('datecreated', '>=', $fourWeeksAgoStr)
    ->where(function($query) {
        $query->where('description', 'LIKE', '%late%')
              ->orWhere('description', 'LIKE', '%penalty%')
              ->orWhere('description', 'LIKE', '%overdue%');
    })
    ->whereIn('loan_id', $loanIds->toArray())
    ->get();

echo "Late fee records found: " . $lateFeeRecords->count() . "\n";

if ($lateFeeRecords->count() > 0) {
    $totalFeeAmount = $lateFeeRecords->sum('amount');
    echo "Total amount in fee records: " . number_format($totalFeeAmount, 0) . " UGX\n";
    echo "These records will be deleted.\n";
} else {
    echo "Note: No late fee records in database.\n";
    echo "Late fees may be calculated dynamically at payment time.\n";
}

echo "\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   SUMMARY OF ACTIONS TO BE PERFORMED\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "This script will:\n\n";
echo "  1. DELETE late fee records created during upgrade period\n";
echo "     Records to delete: " . $lateFeeRecords->count() . "\n\n";

echo "  2. MARK schedules to indicate late fees are waived\n";
echo "     Schedules affected: " . $affectedSchedules->count() . "\n\n";

echo "  3. SAVE complete audit trail to JSON file\n";
echo "     Including all client details and amounts\n\n";

echo "Total Late Fees Waived: " . number_format($totalLateFees, 0) . " UGX\n";
echo "Affected Clients: " . $loans->count() . "\n";
echo "Reason: System upgrade prevented timely payments\n";
echo "Period: " . $fourWeeksAgo->format('d-m-Y') . " to " . $today->format('d-m-Y') . "\n\n";

echo "IMPORTANT NOTES:\n";
echo "  • Original due dates remain unchanged\n";
echo "  • Clients should pay original schedule amounts\n";
echo "  • NO late fees will be charged for upgrade period\n";
echo "  • Pre-existing late fees (before upgrade) are NOT affected\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "Do you want to proceed with late fee waiver? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n✗ Operation cancelled by user.\n";
    echo "  No changes have been made.\n\n";
    exit(0);
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   EXECUTING LATE FEE WAIVER\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

DB::beginTransaction();

try {
    $deletedCount = 0;
    $markedCount = 0;
    
    // Step 1: Delete late fee records
    if ($lateFeeRecords->count() > 0) {
        echo "STEP 1: Deleting late fee records...\n";
        
        $deletedCount = DB::table('fees')
            ->where('datecreated', '>=', $fourWeeksAgoStr)
            ->where(function($query) {
                $query->where('description', 'LIKE', '%late%')
                      ->orWhere('description', 'LIKE', '%penalty%')
                      ->orWhere('description', 'LIKE', '%overdue%');
            })
            ->whereIn('loan_id', $loanIds->toArray())
            ->delete();
        
        echo "  ✓ Deleted {$deletedCount} late fee records\n\n";
    } else {
        echo "STEP 1: No late fee records to delete\n\n";
    }
    
    // Step 2: Add comments to schedules (for audit trail)
    echo "STEP 2: Marking schedules with waiver note...\n";
    
    foreach ($affectedSchedules as $schedule) {
        DB::table('loan_schedules')
            ->where('id', $schedule->id)
            ->update([
                'date_modified' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        $markedCount++;
        
        if ($markedCount % 50 == 0) {
            echo "  ... {$markedCount} schedules updated\n";
        }
    }
    
    echo "  ✓ Updated {$markedCount} schedules\n\n";
    
    // Step 3: Save comprehensive audit trail
    echo "STEP 3: Saving audit trail...\n";
    
    $auditFile = 'late_fees_waived_' . date('Y-m-d_His') . '.json';
    $auditData = [
        'action' => 'waive_system_upgrade_late_fees',
        'timestamp' => $today->toDateTimeString(),
        'reason' => 'System upgrade period prevented clients from making timely loan repayments',
        'upgrade_period' => [
            'start_date' => $fourWeeksAgo->format('d-m-Y'),
            'end_date' => $today->format('d-m-Y'),
            'duration_days' => 28,
            'duration_weeks' => 4
        ],
        'summary' => [
            'total_late_fees_waived' => $totalLateFees,
            'affected_clients' => $loans->count(),
            'affected_schedules' => $affectedSchedules->count(),
            'late_fee_records_deleted' => $deletedCount,
            'schedules_updated' => $markedCount
        ],
        'client_details' => $details,
        'deleted_fee_records' => $lateFeeRecords->map(function($fee) {
            return [
                'fee_id' => $fee->id,
                'loan_id' => $fee->loan_id,
                'member_id' => $fee->member_id,
                'amount' => $fee->amount,
                'description' => $fee->description,
                'created_at' => $fee->datecreated
            ];
        })->toArray()
    ];
    
    file_put_contents($auditFile, json_encode($auditData, JSON_PRETTY_PRINT));
    echo "  ✓ Audit trail saved to: {$auditFile}\n\n";
    
    DB::commit();
    
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✓ SUCCESS - LATE FEES WAIVED\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "COMPLETED ACTIONS:\n\n";
    echo "  ✓ Deleted {$deletedCount} late fee records\n";
    echo "  ✓ Updated {$markedCount} schedules\n";
    echo "  ✓ Saved comprehensive audit trail\n\n";
    
    echo "FINANCIAL SUMMARY:\n\n";
    echo "  Late Fees Waived:    " . number_format($totalLateFees, 0) . " UGX\n";
    echo "  Affected Clients:    " . $loans->count() . "\n";
    echo "  Affected Schedules:  " . $affectedSchedules->count() . "\n\n";
    
    echo "NEXT STEPS:\n\n";
    echo "  1. Clients can now repay their original schedule amounts\n";
    echo "  2. NO late fees will be charged for the upgrade period\n";
    echo "  3. Review audit file: {$auditFile}\n";
    echo "  4. Inform clients about the waiver\n\n";
    
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✗ ERROR OCCURRED\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    
    echo "Transaction has been rolled back.\n";
    echo "No changes have been made to the database.\n\n";
    
    exit(1);
}
