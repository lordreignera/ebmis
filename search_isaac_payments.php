<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "SEARCHING FOR ISAAC'S PAYMENTS\n";
echo str_repeat("=", 80) . "\n\n";

$phone = '0702682187';

// Check repayments table by phone
echo "1. Searching repayments by phone ({$phone}):\n";
$repayments = DB::table('repayments')
    ->where('phone', 'LIKE', "%{$phone}%")
    ->orWhere('phone', 'LIKE', '%256702682187%')
    ->orderBy('id', 'desc')
    ->get();

echo "   Found: " . $repayments->count() . " repayment(s)\n";
foreach ($repayments as $r) {
    echo "   - ID {$r->id}: Loan {$r->loan_id}, Amount: {$r->amount}, Status: {$r->status}, Date: {$r->date_created}\n";
}
echo "\n";

// Check recent repayments (last 7 days)
echo "2. Recent repayments (last 7 days) for all loans:\n";
$recentRepayments = DB::table('repayments')
    ->where('date_created', '>=', date('Y-m-d', strtotime('-7 days')))
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

echo "   Found: " . $recentRepayments->count() . " recent repayment(s)\n";
foreach ($recentRepayments as $r) {
    echo "   - ID {$r->id}: Loan {$r->loan_id}, Amount: {$r->amount}, Status: {$r->status}, Phone: {$r->phone}, Date: {$r->date_created}\n";
}
echo "\n";

// Check if there are pending repayments for loan 133
echo "3. All repayments for loan 133 (Isaac's loan):\n";
$loanRepayments = DB::table('repayments')
    ->where('loan_id', 133)
    ->get();

echo "   Found: " . $loanRepayments->count() . " repayment(s)\n";
if ($loanRepayments->count() > 0) {
    foreach ($loanRepayments as $r) {
        echo "   - ID {$r->id}: Amount: {$r->amount}, Status: {$r->status}, Date: {$r->date_created}\n";
    }
} else {
    echo "   ❌ No repayments recorded for this loan!\n";
}
echo "\n";

// Check disbursement_transactions or raw_payments table
echo "4. Checking raw_payments table:\n";
try {
    $rawPayments = DB::table('raw_payments')
        ->where('msisdn', 'LIKE', "%{$phone}%")
        ->orWhere('msisdn', 'LIKE', '%256702682187%')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    
    echo "   Found: " . $rawPayments->count() . " raw payment(s)\n";
    foreach ($rawPayments as $rp) {
        echo "   - ID {$rp->id}: Amount: {$rp->amount}, Status: {$rp->pay_status}, Date: {$rp->created_at}\n";
    }
} catch (\Exception $e) {
    echo "   Table doesn't exist or error: " . $e->getMessage() . "\n";
}

echo "\n";
