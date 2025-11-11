<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Manually Complete Loan 132 Disbursement\n";
echo str_repeat("=", 80) . "\n\n";

// Get the first disbursement record
$disbursement = DB::table('disbursements')
    ->where('loan_id', 132)
    ->where('loan_type', 1)
    ->first();

if (!$disbursement) {
    echo "No disbursement found!\n";
    exit;
}

echo "Found disbursement ID: " . $disbursement->id . "\n";
echo "Amount: " . $disbursement->amount . "\n\n";

// Get loan details
$loan = DB::table('personal_loans')->where('id', 132)->first();

echo "Loan Details:\n";
echo "  Principal: " . $loan->principal . "\n";
echo "  Period: " . $loan->period . " periods\n";
echo "  Interest: " . $loan->interest . "%\n";
echo "  Installment: " . $loan->installment . "\n\n";

// Update loan status to disbursed
echo "1. Updating loan status to Disbursed (2)...\n";
DB::table('personal_loans')
    ->where('id', 132)
    ->update(['status' => 2]);
echo "   ✅ Done\n\n";

// Generate repayment schedules
echo "2. Generating repayment schedules...\n";

$principal = $loan->principal;
$interest = $loan->interest / 100;
$period = $loan->period;
$installment = $loan->installment;
$periodType = $loan->period_type ?? 3; // Daily

// Calculate per-period interest
if ($periodType == 3) {
    $interestPerPeriod = $interest / 365; // Daily
} elseif ($periodType == 2) {
    $interestPerPeriod = $interest / 12; // Monthly
} else {
    $interestPerPeriod = ($interest * 7) / 365; // Weekly
}

$disbursementDate = \Carbon\Carbon::parse($disbursement->created_at);
$balance = $principal;

for ($i = 1; $i <= $period; $i++) {
    $interestAmount = $balance * $interestPerPeriod;
    $principalAmount = $installment - $interestAmount;
    
    if ($principalAmount > $balance) {
        $principalAmount = $balance;
        $installment = $principalAmount + $interestAmount;
    }
    
    $balance -= $principalAmount;
    
    // Calculate payment date (for daily, add days skipping Sundays)
    $paymentDate = $disbursementDate->copy()->addDays($i + 7);
    if ($paymentDate->isSunday()) {
        $paymentDate->addDay();
    }

    DB::table('loan_schedules')->insert([
        'loan_id' => 132,
        'payment_date' => $paymentDate->format('Y-m-d'),
        'principal' => round($principalAmount, 2),
        'interest' => round($interestAmount, 2),
        'payment' => round($principalAmount + $interestAmount, 2),
        'balance' => round($balance, 2),
        'status' => 0,
        'date_created' => now(),
    ]);

    if ($balance <= 0) break;
}

echo "   ✅ Created " . $i . " repayment schedules\n\n";

// Update disbursement status
echo "3. Updating disbursement status to Approved (1)...\n";
DB::table('disbursements')
    ->where('id', $disbursement->id)
    ->update(['status' => 1]);
echo "   ✅ Done\n\n";

echo "Summary:\n";
echo "========\n";
echo "✅ Loan status updated to Disbursed\n";
echo "✅ " . $i . " repayment schedules created\n";
echo "✅ Disbursement marked as approved\n\n";

echo "Loan 132 is now fully disbursed and ready for repayments!\n";
