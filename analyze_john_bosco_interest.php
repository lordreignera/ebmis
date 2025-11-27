<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== JOHN BOSCO LOAN #105 - INTEREST CALCULATION ANALYSIS ===\n\n";

$principal = 1000000;
$period = 2;
$interestRate = 0.075; // 7.5%

echo "Loan Details:\n";
echo "  Principal: " . number_format($principal) . " UGX\n";
echo "  Interest Rate: " . ($interestRate * 100) . "%\n";
echo "  Period: {$period} installments\n\n";

echo str_repeat('=', 80) . "\n";
echo "OPTION 1: FLAT RATE (Equal Installments)\n";
echo str_repeat('=', 80) . "\n";

$totalInterest = $principal * $interestRate;
$totalPayable = $principal + $totalInterest;
$equalPayment = $totalPayable / $period;

echo "Total Interest: " . number_format($totalInterest) . " UGX\n";
echo "Total Payable: " . number_format($totalPayable) . " UGX\n";
echo "Payment Per Period: " . number_format($equalPayment) . " UGX (EQUAL)\n\n";

echo "Schedule:\n";
for ($i = 1; $i <= $period; $i++) {
    echo "  Period {$i}: " . number_format($equalPayment) . " UGX\n";
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "OPTION 2: DECLINING BALANCE (Current System)\n";
echo str_repeat('=', 80) . "\n";

$principalPerPeriod = $principal / $period;
$remainingPrincipal = $principal;
$totalPaymentDeclining = 0;
$totalInterestDeclining = 0;

echo "Schedule:\n";
for ($i = 1; $i <= $period; $i++) {
    $interestAmount = $remainingPrincipal * $interestRate;
    $payment = $principalPerPeriod + $interestAmount;
    
    echo "  Period {$i}: Payment=" . number_format($payment) . " (Interest=" . number_format($interestAmount) . ", Principal=" . number_format($principalPerPeriod) . ")\n";
    
    $totalPaymentDeclining += $payment;
    $totalInterestDeclining += $interestAmount;
    $remainingPrincipal -= $principalPerPeriod;
}

echo "\nTotal Payment: " . number_format($totalPaymentDeclining) . " UGX\n";
echo "Total Interest: " . number_format($totalInterestDeclining) . " UGX\n";

echo "\n" . str_repeat('=', 80) . "\n";
echo "TO GET 640,000 AS FIRST PAYMENT - WHAT INTEREST RATE IS NEEDED?\n";
echo str_repeat('=', 80) . "\n";

// If first payment should be 640,000 with declining balance
$targetFirstPayment = 640000;
$principalPayment = $principal / $period; // 500,000

// First Payment = Principal Payment + Interest
// 640,000 = 500,000 + Interest
$neededInterest = $targetFirstPayment - $principalPayment;
$neededRate = ($neededInterest / $principal) * 100;

echo "Target First Payment: " . number_format($targetFirstPayment) . " UGX\n";
echo "Principal Component: " . number_format($principalPayment) . " UGX\n";
echo "Interest Component Needed: " . number_format($neededInterest) . " UGX\n";
echo "Interest Rate Needed: {$neededRate}%\n\n";

echo "With {$neededRate}% interest rate (DECLINING BALANCE):\n";
$remainingPrincipal = $principal;
for ($i = 1; $i <= $period; $i++) {
    $interest = $remainingPrincipal * ($neededRate / 100);
    $payment = $principalPayment + $interest;
    echo "  Period {$i}: " . number_format($payment) . " UGX (Interest: " . number_format($interest) . ")\n";
    $remainingPrincipal -= $principalPayment;
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "TO GET 650,000 AS EQUAL PAYMENTS (FLAT RATE)\n";
echo str_repeat('=', 80) . "\n";

// If all payments should be 650,000
$targetEqualPayment = 650000;
$targetTotal = $targetEqualPayment * $period;
$targetInterest = $targetTotal - $principal;
$targetFlatRate = ($targetInterest / $principal) * 100;

echo "Target Payment Per Period: " . number_format($targetEqualPayment) . " UGX\n";
echo "Total Payable: " . number_format($targetTotal) . " UGX\n";
echo "Total Interest: " . number_format($targetInterest) . " UGX\n";
echo "Interest Rate Needed (FLAT): {$targetFlatRate}%\n\n";

echo "With {$targetFlatRate}% interest rate (FLAT RATE):\n";
for ($i = 1; $i <= $period; $i++) {
    echo "  Period {$i}: " . number_format($targetEqualPayment) . " UGX (EQUAL)\n";
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "CURRENT DATABASE VALUES:\n";
echo str_repeat('=', 80) . "\n";

$loan = DB::table('personal_loans')->where('id', 105)->first();
echo "Stored Max Installment: " . number_format($loan->installment) . " UGX\n";
echo "Stored Interest Rate: {$loan->interest}%\n";
echo "Interest Method: " . ($loan->interest_method == 1 ? 'FLAT RATE' : 'DECLINING BALANCE') . "\n";
