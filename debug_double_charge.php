<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== DEBUGGING LATE FEE DOUBLE CHARGE ===\n\n";

$schedule = DB::table('loan_schedules')->where('id', 5204)->first();
$loan = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->where('pl.id', 105)
    ->select('pl.*', 'p.period_type')
    ->first();

echo "Schedule Due Date: {$schedule->payment_date}\n";
echo "Schedule Status: {$schedule->status}\n\n";

// Method 1: Using strtotime (like controller)
$now = time();
$your_date = strtotime($schedule->payment_date);
$datediff = $now - $your_date;
$d = floor($datediff / (60 * 60 * 24));
$dd = $d > 0 ? ceil($d / 30) : 0;

echo "METHOD 1 (strtotime - Controller Method):\n";
echo "  Now timestamp: $now (" . date('Y-m-d H:i:s', $now) . ")\n";
echo "  Due timestamp: $your_date (" . date('Y-m-d H:i:s', $your_date) . ")\n";
echo "  Diff seconds: $datediff\n";
echo "  Days (d): $d\n";
echo "  Periods (dd): $dd\n";
echo "  Late Fee: " . number_format((($schedule->principal + $schedule->interest) * 0.06) * $dd, 0) . " UGX\n\n";

// Method 2: Using Carbon (like Laravel)
try {
    $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
    $today = Carbon::now();
    $days2 = $dueDate->diffInDays($today);
    $periods2 = ceil($days2 / 30);
    
    echo "METHOD 2 (Carbon):\n";
    echo "  Due Date: " . $dueDate->format('Y-m-d H:i:s') . "\n";
    echo "  Today: " . $today->format('Y-m-d H:i:s') . "\n";
    echo "  Days: $days2\n";
    echo "  Periods: $periods2\n";
    echo "  Late Fee: " . number_format((($schedule->principal + $schedule->interest) * 0.06) * $periods2, 0) . " UGX\n\n";
} catch (\Exception $e) {
    echo "Carbon Error: " . $e->getMessage() . "\n\n";
}

// Check if there's a date_cleared value
echo "Date Cleared: " . ($schedule->date_cleared ?? 'NULL') . "\n";
if ($schedule->date_cleared) {
    $now_cleared = strtotime($schedule->date_cleared);
    $diff_cleared = $now_cleared - $your_date;
    $d_cleared = floor($diff_cleared / (60 * 60 * 24));
    echo "  Using date_cleared instead of now!\n";
    echo "  Days overdue would be: $d_cleared\n";
}

echo "\n=== CHECKING ACTUAL WEB VIEW DATA ===\n";
echo "What the view receives:\n";
echo "  schedule->principal: " . number_format($schedule->principal, 0) . "\n";
echo "  schedule->interest: " . number_format($schedule->interest, 0) . "\n";
echo "  Total: " . number_format($schedule->principal + $schedule->interest, 0) . "\n\n";

// Check if maybe it's calculating twice
echo "POSSIBLE DOUBLE CHARGE SCENARIOS:\n";
echo "  Scenario 1 - Calculated twice in different places: " . number_format((($schedule->principal + $schedule->interest) * 0.06) * $dd * 2, 0) . " UGX\n";
echo "  Scenario 2 - Using wrong period_type: " . number_format((($schedule->principal + $schedule->interest) * 0.06) * ($dd * 2), 0) . " UGX\n";
echo "  Scenario 3 - Wrong rate (12% instead of 6%): " . number_format((($schedule->principal + $schedule->interest) * 0.12) * $dd, 0) . " UGX\n\n";

// Check the late_fees table
$lateFeeRecord = DB::table('late_fees')->where('schedule_id', 5204)->first();
if ($lateFeeRecord) {
    echo "LATE_FEES TABLE RECORD:\n";
    echo "  Amount: " . number_format($lateFeeRecord->amount, 0) . " UGX\n";
    echo "  Days Overdue: {$lateFeeRecord->days_overdue}\n";
    echo "  Periods Overdue: {$lateFeeRecord->periods_overdue}\n";
    echo "  Calculated Date: {$lateFeeRecord->calculated_date}\n";
}

echo "\n";
