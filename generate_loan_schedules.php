<?php
/**
 * Generate repayment schedules for loan PDLOAN2511120004001
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PersonalLoan;
use App\Models\Disbursement;

echo "Generating schedules for loan PDLOAN2511120004001...\n\n";

$loan = PersonalLoan::find(135);
$disbursement = Disbursement::where('loan_id', 135)->where('loan_type', 1)->first();

if (!$loan || !$disbursement) {
    echo "❌ Loan or disbursement not found!\n";
    exit(1);
}

echo "Loan Details:\n";
echo "  Principal: UGX " . number_format($loan->principal, 0) . "\n";
echo "  Interest: {$loan->interest}%\n";
echo "  Period: {$loan->period} installments\n";
echo "  Installment: UGX " . number_format($loan->installment, 0) . "\n";
echo "  Disbursement Date: {$disbursement->created_at}\n\n";

// Generate schedules
$principal = $loan->principal;
$interest = $loan->interest / 100;
$period = $loan->period;
$periodType = $loan->period_type ?? 3; // Default to daily (3)
$disbursementDate = \Carbon\Carbon::parse($disbursement->created_at);
$installment = $loan->installment;

// Calculate per-period interest
$interestPerPeriod = $interest / 365; // Daily
if ($periodType == 1) {
    $interestPerPeriod = ($interest * 7) / 365; // Weekly
} elseif ($periodType == 2) {
    $interestPerPeriod = $interest / 12; // Monthly
}

$balance = $principal;

echo "Generating {$period} repayment schedules...\n";
echo "========================================\n";

for ($i = 1; $i <= $period; $i++) {
    $interestAmount = $balance * $interestPerPeriod;
    $principalAmount = $installment - $interestAmount;
    
    if ($principalAmount > $balance) {
        $principalAmount = $balance;
        $installment = $principalAmount + $interestAmount;
    }
    
    $balance -= $principalAmount;
    
    // Calculate payment date based on period type
    if ($periodType == 1) { // Weekly
        $paymentDate = $disbursementDate->copy()->addWeeks($i);
    } elseif ($periodType == 2) { // Monthly
        $paymentDate = $disbursementDate->copy()->addMonths($i);
    } else { // Daily
        $paymentDate = $disbursementDate->copy()->addDays($i);
    }

    DB::table('loan_schedules')->insert([
        'loan_id' => $loan->id,
        'payment_date' => $paymentDate->format('Y-m-d'),
        'principal' => round($principalAmount, 2),
        'interest' => round($interestAmount, 2),
        'payment' => round($installment, 2),
        'balance' => round($balance, 2),
        'status' => 0,
        'date_created' => now(),
    ]);

    echo "Schedule {$i}: " . $paymentDate->format('Y-m-d') . " | ";
    echo "Principal: " . number_format($principalAmount, 0) . " | ";
    echo "Interest: " . number_format($interestAmount, 0) . " | ";
    echo "Payment: " . number_format($installment, 0) . " | ";
    echo "Balance: " . number_format($balance, 0) . "\n";

    if ($balance <= 0) break;
}

$scheduleCount = DB::table('loan_schedules')->where('loan_id', $loan->id)->count();

echo "\n========================================\n";
echo "✅ {$scheduleCount} schedules generated successfully!\n";
echo "========================================\n\n";

echo "You can now view the loan at:\n";
echo "  http://localhost:84/admin/loans/active?type=personal\n";
echo "  http://localhost:84/admin/loans/{$loan->id}\n\n";
