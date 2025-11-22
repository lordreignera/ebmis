<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "INVESTIGATING ISAAC'S PAYMENT TRANSACTION\n";
echo str_repeat("=", 80) . "\n\n";

$loanId = 133;
$phone = '0702682187';
$altPhone = '256702682187';

// 1. Check if there are any raw_payments for this phone
echo "1. Checking raw_payments table for phone {$phone}:\n";
try {
    $rawPayments = DB::table('raw_payments')
        ->where(function($q) use ($phone, $altPhone) {
            $q->where('msisdn', 'LIKE', "%{$phone}%")
              ->orWhere('msisdn', 'LIKE', "%{$altPhone}%");
        })
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    
    if ($rawPayments->count() > 0) {
        echo "   Found " . $rawPayments->count() . " raw payment(s):\n";
        foreach ($rawPayments as $rp) {
            echo "   - ID {$rp->id}: Amount={$rp->amount}, Status={$rp->pay_status}, Date={$rp->created_at}\n";
            echo "     TxnRef: {$rp->txn_ref}\n";
            echo "     Message: {$rp->raw_message}\n\n";
        }
    } else {
        echo "   ❌ No raw payments found for this phone\n\n";
    }
} catch (\Exception $e) {
    echo "   Table doesn't exist or error: " . $e->getMessage() . "\n\n";
}

// 2. Check disbursement_transactions table
echo "2. Checking disbursement_transactions table:\n";
try {
    $disbTransactions = DB::table('disbursement_transactions')
        ->where('loan_id', $loanId)
        ->orderBy('id', 'desc')
        ->get();
    
    if ($disbTransactions->count() > 0) {
        echo "   Found " . $disbTransactions->count() . " transaction(s):\n";
        foreach ($disbTransactions as $dt) {
            echo "   - ID {$dt->id}: Amount={$dt->amount}, Status={$dt->status}, Date={$dt->datecreated}\n";
        }
    } else {
        echo "   No disbursement transactions for this loan\n";
    }
} catch (\Exception $e) {
    echo "   Table doesn't exist: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Check if transaction ID 135243353857 exists anywhere
echo "3. Searching for transaction ID '135243353857':\n";

// Check in repayments
$txnInRepayments = DB::table('repayments')
    ->where('txn_id', 'LIKE', '%135243353857%')
    ->orWhere('transaction_reference', 'LIKE', '%135243353857%')
    ->get();

if ($txnInRepayments->count() > 0) {
    echo "   Found in repayments table:\n";
    foreach ($txnInRepayments as $r) {
        echo "   - Repayment ID {$r->id}: Loan {$r->loan_id}, Amount={$r->amount}, Status={$r->status}\n";
    }
} else {
    echo "   Not found in repayments table\n";
}
echo "\n";

// Check in raw_payments if exists
try {
    $txnInRaw = DB::table('raw_payments')
        ->where('txn_ref', 'LIKE', '%135243353857%')
        ->orWhere('raw_message', 'LIKE', '%135243353857%')
        ->get();
    
    if ($txnInRaw->count() > 0) {
        echo "   Found in raw_payments table:\n";
        foreach ($txnInRaw as $r) {
            echo "   - ID {$r->id}: Amount={$r->amount}, Phone={$r->msisdn}, Status={$r->pay_status}\n";
        }
    } else {
        echo "   Not found in raw_payments table\n";
    }
} catch (\Exception $e) {
    echo "   raw_payments table doesn't exist\n";
}
echo "\n";

// 4. Check current schedule status
echo "4. Current schedule status for loan 133:\n";
$schedules = DB::table('loan_schedules')
    ->where('loan_id', $loanId)
    ->get();

foreach ($schedules as $i => $s) {
    $status = $s->status == 0 ? 'PENDING' : 'PAID';
    echo "   Schedule " . ($i + 1) . " (ID {$s->id}):\n";
    echo "     Due: {$s->payment_date}\n";
    echo "     Amount: {$s->payment} UGX\n";
    echo "     Paid: {$s->paid} UGX\n";
    echo "     Status: {$s->status} ({$status})\n";
    echo "     Date Cleared: " . ($s->date_cleared ?? 'NULL') . "\n\n";
}

// 5. Check the repayment we just created
echo "5. Checking repayment ID 946 (just created):\n";
$repayment = DB::table('repayments')->where('id', 946)->first();
if ($repayment) {
    echo "   Repayment ID: 946\n";
    echo "   Loan ID: {$repayment->loan_id}\n";
    echo "   Schedule ID: {$repayment->schedule_id}\n";
    echo "   Amount: {$repayment->amount} UGX\n";
    echo "   Status: {$repayment->status} (" . ($repayment->status == 0 ? 'PENDING' : 'APPROVED') . ")\n";
    echo "   Date: {$repayment->date_created}\n";
    echo "   Transaction ID: {$repayment->txn_id}\n\n";
} else {
    echo "   ❌ Repayment 946 not found!\n\n";
}

echo str_repeat("=", 80) . "\n";
echo "CONCLUSIONS:\n";
echo str_repeat("=", 80) . "\n\n";

echo "Based on the investigation:\n";
echo "1. If no raw_payments exist = Payment never reached the database initially\n";
echo "2. Transaction ID 135243353857 was provided by you (not from DB)\n";
echo "3. The amount 5,678 UGX was provided by you (includes late fees)\n";
echo "4. This was a MANUAL entry based on information you provided\n\n";

echo "Why didn't the original payment save?\n";
echo "Possible reasons:\n";
echo "  - Old system client: Payment form may have different validation\n";
echo "  - Missing schedule_id: Old loans might not link properly to schedules\n";
echo "  - Validation error: Some field might have failed validation\n";
echo "  - JavaScript error: Frontend might have failed to submit\n\n";

echo "Why is schedule still showing as PENDING in UI?\n";
echo "  - Cache issue: Browser/Laravel cache needs clearing\n";
echo "  - View issue: Active loans page might need refresh\n";
echo "  - Status column: Check if status field was actually updated\n\n";
