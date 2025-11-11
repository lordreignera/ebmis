<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Loan 132 Disbursement Status:\n";
echo str_repeat("=", 80) . "\n\n";

// Check loan status
$loan = DB::table('personal_loans')->where('id', 132)->first();
if ($loan) {
    echo "Loan Status: " . $loan->status . "\n";
    echo "  0 = Pending\n";
    echo "  1 = Approved\n";
    echo "  2 = Disbursed\n";
    echo "  3 = Completed\n";
    echo "  4 = Rejected\n\n";
}

// Check disbursements
echo "Disbursement Records:\n";
echo str_repeat("-", 80) . "\n";
$disbursements = DB::table('disbursements')->where('loan_id', 132)->get();

if ($disbursements->isEmpty()) {
    echo "No disbursement records found!\n";
} else {
    foreach ($disbursements as $disb) {
        echo sprintf("ID: %d\n", $disb->id);
        echo sprintf("Amount: %s\n", $disb->amount);
        echo sprintf("Status: %d (0=Pending, 1=Approved, 2=Disbursed)\n", $disb->status);
        echo sprintf("Payment Type: %d (1=Mobile Money, 2=Bank/Cheque/Cash)\n", $disb->payment_type);
        echo sprintf("Account: %s\n", $disb->account_number);
        echo sprintf("Investment ID: %d\n", $disb->inv_id);
        echo sprintf("Created: %s\n", $disb->created_at);
        echo str_repeat("-", 80) . "\n";
    }
}

// Check loan charges
echo "\nLoan Charges Created:\n";
echo str_repeat("-", 80) . "\n";
$charges = DB::table('loan_charges')->where('loan_id', 132)->get();

if ($charges->isEmpty()) {
    echo "No charges recorded!\n";
} else {
    foreach ($charges as $charge) {
        echo sprintf("Charge: %s - %s (%s)\n", 
            $charge->charge_name, 
            $charge->actual_value,
            $charge->charge_type == 1 ? 'Fixed' : 'Percentage'
        );
    }
}

// Check raw payments
echo "\nRaw Payments (Mobile Money):\n";
echo str_repeat("-", 80) . "\n";
$payments = DB::table('raw_payments')->where('type', 'disbursement')->orderBy('id', 'desc')->limit(3)->get();

if ($payments->isEmpty()) {
    echo "No raw payment records found!\n";
} else {
    foreach ($payments as $payment) {
        echo sprintf("ID: %d - Phone: %s - Amount: %s - Status: %s - Created: %s\n",
            $payment->id,
            $payment->phone,
            $payment->amount,
            $payment->status,
            $payment->date_created
        );
    }
}
