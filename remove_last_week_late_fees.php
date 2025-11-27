<?php

/**
 * Waive Late Fees from System Upgrade Period (Last 4 Weeks)
 * 
 * This waives late fees ONLY for schedules that became overdue during
 * the system upgrade period (last 4 weeks) when clients couldn't pay.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n\n";
echo "   WAIVE LATE FEES FROM SYSTEM UPGRADE PERIOD\n";
echo "   Last 4 Weeks - When Clients Couldn't Repay\n";
echo "\n\n";

$fourWeeksAgo = Carbon::now()->subWeeks(4);
$today = Carbon::now();

echo "System Upgrade Period: " . $fourWeeksAgo->format('d-m-Y') . " to " . $today->format('d-m-Y') . "\n";
echo "Reason: Clients unable to repay during system upgrade\n";
echo "Date: " . $today->format('Y-m-d H:i:s') . "\n\n";

echo "1. Finding schedules that became overdue during system upgrade...\n";
echo "   (Schedules with due dates in last 4 weeks)\n\n";

// Get all unpaid schedules
$allUnpaid = DB::table('loan_schedules')->where('status', 0)->get();

// Filter schedules that became overdue during upgrade period
$affectedSchedules = $allUnpaid->filter(function($schedule) use ($fourWeeksAgo, $today) {
    $parts = explode('-', $schedule->payment_date);
    if (count($parts) == 3) {
        try {
            $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
            // Only schedules that were DUE during the upgrade period
            return $dueDate->between($fourWeeksAgo, $today);
        } catch (\Exception $e) {
            return false;
        }
    }
    return false;
});

echo "   Found: " . $affectedSchedules->count() . " schedules that became overdue during upgrade\n\n";

if ($affectedSchedules->count() == 0) {
    echo " No schedules became overdue during system upgrade period.\n\n";
    exit(0);
}

echo "2. Calculating late fees to waive...\n\n";

$loanIds = $affectedSchedules->pluck('loan_id')->unique();
$loans = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->whereIn('pl.id', $loanIds)
    ->select('pl.*', 'p.period_type')
    ->get()
    ->keyBy('id');

$totalLateFees = 0;
$details = [];
$processedCount = 0;

foreach ($affectedSchedules as $schedule) {
    $loan = $loans->get($schedule->loan_id);
    if (!$loan) continue;
    
    $member = DB::table('members')->where('id', $loan->member_id)->first();
    $memberName = $member ? "{$member->fname} {$member->lname}" : 'Unknown';
    
    $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
    $daysOverdue = $dueDate->diffInDays($today);
    
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
    
    $scheduleAmount = $schedule->principal + $schedule->interest;
    $lateFee = ($scheduleAmount * 0.06) * $periodsOverdue;
    $totalLateFees += $lateFee;
    
    $processedCount++;
    
    $details[] = [
        'schedule_id' => $schedule->id,
        'loan_id' => $loan->id,
        'loan_code' => $loan->code,
        'member_name' => $memberName,
        'due_date' => $schedule->payment_date,
        'days_overdue' => $daysOverdue,
        'periods_overdue' => $periodsOverdue,
        'late_fee_waived' => $lateFee,
        'period_type' => $periodType
    ];
}

echo "   Schedules analyzed: {$processedCount}\n";
echo "   Total late fees to waive: " . number_format($totalLateFees, 0) . " UGX\n";
echo "   Affected loans: " . $loanIds->count() . "\n\n";

// Show some examples
echo "Examples of affected clients:\n";
for ($i = 0; $i < min(5, count($details)); $i++) {
    $d = $details[$i];
    echo "    {$d['member_name']} - Loan {$d['loan_code']}\n";
    echo "     Due: {$d['due_date']} | {$d['days_overdue']} days overdue\n";
    echo "     Late fee to waive: " . number_format($d['late_fee_waived'], 0) . " UGX\n\n";
}

echo "\n";
echo "   ACTIONS TO BE PERFORMED\n";
echo "\n\n";
echo "This script will:\n";
echo "  1. Reschedule " . $affectedSchedules->count() . " overdue payments to next period\n";
echo "  2. Waive " . number_format($totalLateFees, 0) . " UGX in late fees\n";
echo "  3. Delete late fee records from upgrade period\n";
echo "  4. Save audit trail to JSON file\n\n";
echo "Reason: System upgrade prevented clients from making payments\n";
echo "Period: Last 4 weeks (" . $fourWeeksAgo->format('d-m-Y') . " to " . $today->format('d-m-Y') . ")\n\n";
echo "Do you want to proceed? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n Operation cancelled.\n\n";
    exit(0);
}

echo "\n\nStarting late fee waiver process...\n\n";

DB::beginTransaction();

try {
    $rescheduledCount = 0;
    $deletedFeesCount = 0;
    
    echo "3. Rescheduling overdue payments...\n";
    
    foreach ($affectedSchedules as $schedule) {
        $loan = $loans->get($schedule->loan_id);
        if (!$loan) continue;
        
        $newDate = Carbon::now();
        
        if ($loan->period_type == '1') {
            // Weekly - next Friday
            $newDate->addWeek();
            while ($newDate->dayOfWeek !== Carbon::FRIDAY) {
                $newDate->addDay();
            }
        } else if ($loan->period_type == '2') {
            // Monthly - 25th of next month
            $newDate->addMonth()->day(25);
        } else if ($loan->period_type == '3') {
            // Daily - tomorrow
            $newDate->addDay();
        }
        
        DB::table('loan_schedules')
            ->where('id', $schedule->id)
            ->update([
                'payment_date' => $newDate->format('d-m-Y'),
                'date_modified' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        
        $rescheduledCount++;
        
        if ($rescheduledCount % 50 == 0) {
            echo "   ... {$rescheduledCount} schedules rescheduled\n";
        }
    }
    
    echo "    Total rescheduled: {$rescheduledCount}\n";
    
    echo "\n4. Removing late fee records...\n";
    
    $fourWeeksAgoStr = $fourWeeksAgo->format('Y-m-d');
    $deletedFeesCount = DB::table('fees')
        ->where('created_at', '>=', $fourWeeksAgoStr)
        ->where(function($query) {
            $query->where('description', 'LIKE', '%late%')
                  ->orWhere('description', 'LIKE', '%penalty%')
                  ->orWhere('description', 'LIKE', '%overdue%');
        })
        ->whereIn('loan_id', $loanIds->toArray())
        ->delete();
    
    echo "   Deleted {$deletedFeesCount} late fee records\n\n";
    
    echo "5. Saving audit trail...\n";
    
    $auditFile = 'late_fees_waived_upgrade_' . date('Y-m-d_His') . '.json';
    $auditData = [
        'timestamp' => $today->toDateTimeString(),
        'action' => 'waive_system_upgrade_late_fees',
        'reason' => 'System upgrade prevented clients from making timely payments',
        'period' => $fourWeeksAgo->format('d-m-Y') . " to " . $today->format('d-m-Y'),
        'summary' => [
            'schedules_rescheduled' => $rescheduledCount,
            'late_fees_waived' => $totalLateFees,
            'late_fee_records_deleted' => $deletedFeesCount,
            'affected_loans' => $loanIds->count()
        ],
        'details' => $details
    ];
    
    file_put_contents($auditFile, json_encode($auditData, JSON_PRETTY_PRINT));
    echo "    Audit saved to: {$auditFile}\n\n";
    
    DB::commit();
    
    echo "\n";
    echo "    SUCCESS - LATE FEES WAIVED\n";
    echo "\n\n";
    echo "Summary:\n";
    echo "   System upgrade period: Last 4 weeks\n";
    echo "   Schedules rescheduled: {$rescheduledCount}\n";
    echo "   Affected loans: " . $loanIds->count() . "\n";
    echo "   Late fees waived: " . number_format($totalLateFees, 0) . " UGX\n";
    echo "   Late fee records deleted: {$deletedFeesCount}\n";
    echo "   Audit trail: {$auditFile}\n\n";
    echo " All affected clients have been given fair treatment.\n";
    echo " No late fees charged for schedules due during system upgrade.\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n\n";
    echo "    ERROR\n";
    echo "\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Transaction rolled back - no changes made.\n\n";
    exit(1);
}
