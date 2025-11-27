<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "LIVE CALCULATION DEBUG - Schedule #5204\n";
echo "========================================\n\n";

$schedule = DB::table('loan_schedules')->where('id', 5204)->first();
$loan = DB::table('personal_loans')->where('id', $schedule->loan_id)->first();

echo "Schedule ID: {$schedule->id}\n";
echo "Due Date: {$schedule->payment_date}\n";
echo "Status: {$schedule->status}\n";
echo "Principal: " . number_format($schedule->principal, 0) . " UGX\n";
echo "Interest: " . number_format($schedule->interest, 0) . " UGX\n";
echo "Loan Period Type: " . ($loan->period ?? 'NULL') . " (2=Monthly)\n\n";

// EXACT calculation from controller
$now = $schedule->date_cleared ? strtotime($schedule->date_cleared) : time();
$your_date = strtotime($schedule->payment_date);
$datediff = $now - $your_date;
$d = floor($datediff / (60 * 60 * 24));

echo "Date Calculation:\n";
echo "  Today: " . date('Y-m-d H:i:s', $now) . "\n";
echo "  Due Date: " . date('Y-m-d H:i:s', $your_date) . "\n";
echo "  Days Overdue (d): $d days\n\n";

$dd = 0;
if ($d > 0) {
    $period_type = $loan->period ?? '2';
    if ($period_type == '2') {
        $dd = ceil($d / 30);
        echo "Period Calculation (Monthly):\n";
        echo "  Formula: ceil($d / 30)\n";
        echo "  Division: $d / 30 = " . ($d / 30) . "\n";
        echo "  ceil(" . ($d / 30) . ") = $dd\n\n";
    }
}

$intrestamtpayable = $schedule->interest;
$latepayment = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;

echo "Late Fee Calculation:\n";
echo "  Formula: ((principal + interest) × 0.06) × periods\n";
echo "  = (({$schedule->principal} + {$intrestamtpayable}) × 0.06) × $dd\n";
$sum = $schedule->principal + $intrestamtpayable;
echo "  = ({$sum} × 0.06) × $dd\n";
$fee_per_period = $sum * 0.06;
echo "  = {$fee_per_period} × $dd\n";
echo "  = " . number_format($latepayment, 0) . " UGX\n\n";

echo "========================================\n";
echo "RESULT: Periods = $dd, Late Fee = " . number_format($latepayment, 0) . " UGX\n";
echo "========================================\n\n";

echo "⚠️ If you see 2 periods on the web, but this shows 1 period:\n";
echo "   - The code WAS calculating wrong before\n";
echo "   - You need to refresh the page (Ctrl+F5)\n";
echo "   - If still showing 2, check if there's caching\n\n";
