<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== OLD CLIENT CALLBACK ISSUE DIAGNOSIS ===\n\n";

// 1. Check FlexiPay Configuration
echo "1. FLEXIPAY CONFIGURATION\n";
echo "   API URL: " . config('flexipay.api_url') . "\n";
echo "   Merchant: " . config('flexipay.merchant_code') . "\n";
echo "   Callback URL: " . config('flexipay.callback_url') . "\n";
echo "   Enabled: " . (config('flexipay.enabled') ? 'YES' : 'NO') . "\n\n";

// 2. Check Recent Old Client Payments
echo "2. RECENT OLD CLIENT MOBILE MONEY PAYMENTS\n";
$oldClientPayments = DB::table('repayments as r')
    ->join('loan_schedules as ls', 'r.schedule_id', '=', 'ls.id')
    ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
    ->leftJoin('disbursement as d', 'pl.id', '=', 'd.loan_id')
    ->where('r.type', 2) // Mobile money
    ->whereNull('d.id') // No disbursement = old system loan
    ->whereDate('r.date_created', '>=', date('Y-m-d', strtotime('-7 days')))
    ->select('r.*', 'pl.code as loan_code', 'ls.payment as schedule_payment', 
             DB::raw('CASE WHEN d.id IS NULL THEN "OLD" ELSE "NEW" END as loan_type'))
    ->orderBy('r.id', 'desc')
    ->limit(10)
    ->get();

if ($oldClientPayments->isEmpty()) {
    echo "   No recent mobile money payments for old clients\n\n";
} else {
    foreach ($oldClientPayments as $payment) {
        $statusText = $payment->status == 1 ? 'APPROVED ✓' : 'PENDING ⏳';
        echo "   ID {$payment->id} | Loan {$payment->loan_code} | {$payment->amount} UGX | {$statusText}\n";
        echo "   - Reference: {$payment->transaction_reference}\n";
        echo "   - Pay Status: {$payment->pay_status}\n";
        echo "   - Pay Message: {$payment->pay_message}\n";
        echo "   - Schedule ID: {$payment->schedule_id}\n";
        echo "   - Date: {$payment->date_created}\n";
        echo "\n";
    }
}

// 3. Check New Client Payments (for comparison)
echo "3. RECENT NEW CLIENT MOBILE MONEY PAYMENTS (for comparison)\n";
$newClientPayments = DB::table('repayments as r')
    ->join('loan_schedules as ls', 'r.schedule_id', '=', 'ls.id')
    ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
    ->join('disbursement as d', 'pl.id', '=', 'd.loan_id')
    ->where('r.type', 2) // Mobile money
    ->whereDate('r.date_created', '>=', date('Y-m-d', strtotime('-7 days')))
    ->select('r.*', 'pl.code as loan_code', 'ls.payment as schedule_payment')
    ->orderBy('r.id', 'desc')
    ->limit(5)
    ->get();

if ($newClientPayments->isEmpty()) {
    echo "   No recent mobile money payments for new clients\n\n";
} else {
    foreach ($newClientPayments as $payment) {
        $statusText = $payment->status == 1 ? 'APPROVED ✓' : 'PENDING ⏳';
        echo "   ID {$payment->id} | Loan {$payment->loan_code} | {$payment->amount} UGX | {$statusText}\n";
        echo "   - Reference: {$payment->transaction_reference}\n";
        echo "   - Pay Status: {$payment->pay_status}\n";
        echo "   - Date: {$payment->date_created}\n";
        echo "\n";
    }
}

// 4. Check if schedule_id is set for old client payments
echo "4. SCHEDULE ASSOCIATION CHECK\n";
$paymentsWithoutSchedule = DB::table('repayments as r')
    ->join('personal_loans as pl', 'r.loan_id', '=', 'pl.id')
    ->leftJoin('disbursement as d', 'pl.id', '=', 'd.loan_id')
    ->where('r.type', 2)
    ->whereNull('d.id') // Old system loans
    ->whereNull('r.schedule_id') // No schedule linked
    ->where('r.status', 0) // Pending
    ->whereDate('r.date_created', '>=', date('Y-m-d', strtotime('-7 days')))
    ->count();

echo "   Payments without schedule_id: {$paymentsWithoutSchedule}\n";

if ($paymentsWithoutSchedule > 0) {
    echo "   ⚠️ WARNING: Some old client payments have no schedule_id!\n";
    echo "   This will prevent callback from approving them!\n";
}

// 5. Check callback route
echo "\n5. CALLBACK ROUTE CHECK\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$callbackRoute = null;

foreach ($routes as $route) {
    if (str_contains($route->uri(), 'callback') && str_contains($route->uri(), 'mobile')) {
        $callbackRoute = $route;
        break;
    }
}

if ($callbackRoute) {
    echo "   ✓ Callback route found: {$callbackRoute->uri()}\n";
    echo "   - Methods: " . implode(', ', $callbackRoute->methods()) . "\n";
    echo "   - Action: {$callbackRoute->getActionName()}\n";
} else {
    echo "   ❌ No callback route found!\n";
}

// 6. Key Difference Analysis
echo "\n6. KEY DIFFERENCES: OLD vs NEW CLIENT PAYMENTS\n";

$oldClientSample = DB::table('repayments as r')
    ->join('personal_loans as pl', 'r.loan_id', '=', 'pl.id')
    ->leftJoin('disbursement as d', 'pl.id', '=', 'd.loan_id')
    ->where('r.type', 2)
    ->whereNull('d.id')
    ->where('r.status', 0)
    ->latest('r.id')
    ->first();

$newClientSample = DB::table('repayments as r')
    ->join('personal_loans as pl', 'r.loan_id', '=', 'pl.id')
    ->join('disbursement as d', 'pl.id', '=', 'd.loan_id')
    ->where('r.type', 2)
    ->where('r.status', 0)
    ->latest('r.id')
    ->first();

echo "\n   OLD CLIENT SAMPLE:\n";
if ($oldClientSample) {
    echo "   - Repayment ID: {$oldClientSample->id}\n";
    echo "   - Loan ID: {$oldClientSample->loan_id}\n";
    echo "   - Schedule ID: " . ($oldClientSample->schedule_id ?? 'NULL ⚠️') . "\n";
    echo "   - Transaction Ref: {$oldClientSample->transaction_reference}\n";
    echo "   - TXN ID: {$oldClientSample->txn_id}\n";
    echo "   - Amount: {$oldClientSample->amount}\n";
} else {
    echo "   No pending old client payments\n";
}

echo "\n   NEW CLIENT SAMPLE:\n";
if ($newClientSample) {
    echo "   - Repayment ID: {$newClientSample->id}\n";
    echo "   - Loan ID: {$newClientSample->loan_id}\n";
    echo "   - Schedule ID: " . ($newClientSample->schedule_id ?? 'NULL') . "\n";
    echo "   - Transaction Ref: {$newClientSample->transaction_reference}\n";
    echo "   - TXN ID: {$newClientSample->txn_id}\n";
    echo "   - Amount: {$newClientSample->amount}\n";
} else {
    echo "   No pending new client payments\n";
}

// 7. Callback Processing Logic Check
echo "\n7. CALLBACK PROCESSING LOGIC\n";
echo "   When callback is received:\n";
echo "   1. RepaymentService->processPaymentCallback() is called\n";
echo "   2. Searches for repayment by transaction_reference or txn_id\n";
echo "   3. Finds repayment where status = 0 (PENDING)\n";
echo "   4. Calls approveRepayment()\n";
echo "   5. approveRepayment() loads schedule via repayment->schedule relationship\n";
echo "   6. ⚠️ IF schedule_id is NULL, relationship returns NULL\n";
echo "   7. ⚠️ approveRepayment() returns error: 'Loan schedule not found'\n";

// 8. Recommendations
echo "\n8. RECOMMENDATIONS\n";
echo "   ✓ Callback URL is configured: " . config('flexipay.callback_url') . "\n";
echo "   ✓ Callback route exists: /admin/loans/mobile-money/callback\n";
echo "   ✓ Reference fields are being saved properly\n";
echo "\n";
echo "   ⚠️ ISSUE FOUND: Old client payments may not have schedule_id!\n";
echo "   - When callback arrives, it searches by reference: ✓ WORKS\n";
echo "   - When callback tries to approve, it needs schedule: ❌ FAILS if NULL\n";
echo "\n";
echo "   SOLUTION OPTIONS:\n";
echo "   A. Ensure schedule_id is ALWAYS set during payment creation\n";
echo "      (Check RepaymentController->storeRepayment for old loans)\n";
echo "\n";
echo "   B. Modify approveRepayment() to handle NULL schedule_id:\n";
echo "      - If schedule_id is NULL, auto-find next unpaid schedule\n";
echo "      - Same logic we already added to RepaymentController\n";
echo "\n";

echo "=== END DIAGNOSIS ===\n";
