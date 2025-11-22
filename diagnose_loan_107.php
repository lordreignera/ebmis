<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSING LOAN 107 PAYMENT ISSUE ===\n\n";

// Get loan details
$loan = DB::table('personal_loans')->where('id', 107)->first();

if (!$loan) {
    echo "❌ Loan 107 not found\n";
    exit(1);
}

echo "LOAN 107 DETAILS:\n";
echo "   Code: {$loan->code}\n";
echo "   Status: {$loan->status} (" . ($loan->status == 2 ? 'ACTIVE' : ($loan->status == 3 ? 'CLOSED' : 'OTHER')) . ")\n";
echo "   Member ID: {$loan->member_id}\n";
if (isset($loan->principal)) {
    echo "   Principal: {$loan->principal}\n";
}
if (isset($loan->outstanding_balance)) {
    echo "   Outstanding Balance: {$loan->outstanding_balance}\n";
}
echo "\n";

// Check if old system loan
$disbursement = DB::table('disbursement')->where('loan_id', 107)->first();
$loanType = $disbursement ? 'NEW SYSTEM' : 'OLD SYSTEM';
echo "   Loan Type: {$loanType}\n\n";

// Get all schedules
echo "LOAN SCHEDULES:\n";
$schedules = DB::table('loan_schedules')
    ->where('loan_id', 107)
    ->orderBy('id')
    ->get();

foreach ($schedules as $schedule) {
    $statusText = $schedule->status == 1 ? 'PAID ✓' : 'PENDING ⏳';
    $balance = $schedule->payment - $schedule->paid;
    echo "   Schedule {$schedule->id}:\n";
    echo "   - Due: {$schedule->payment} UGX\n";
    echo "   - Paid: {$schedule->paid} UGX\n";
    echo "   - Balance: {$balance} UGX\n";
    echo "   - Status: {$statusText}\n";
    if (isset($schedule->date_due)) {
        echo "   - Due Date: {$schedule->date_due}\n";
    }
    echo "\n";
}

// Get all repayments
echo "ALL REPAYMENTS FOR LOAN 107:\n";
$repayments = DB::table('repayments')
    ->where('loan_id', 107)
    ->orderBy('id', 'desc')
    ->get();

if ($repayments->isEmpty()) {
    echo "   ❌ NO REPAYMENTS FOUND!\n";
    echo "   This means no payments have been recorded yet.\n\n";
} else {
    foreach ($repayments as $rep) {
        $statusText = $rep->status == 1 ? 'APPROVED ✓' : 'PENDING ⏳';
        $typeText = $rep->type == 1 ? 'Cash' : ($rep->type == 2 ? 'Mobile Money' : 'Bank');
        
        echo "   Repayment {$rep->id}:\n";
        echo "   - Amount: {$rep->amount} UGX\n";
        echo "   - Type: {$typeText}\n";
        echo "   - Status: {$statusText}\n";
        echo "   - Schedule ID: " . ($rep->schedule_id ?? 'NULL') . "\n";
        echo "   - Reference: {$rep->transaction_reference}\n";
        echo "   - Pay Status: {$rep->pay_status}\n";
        echo "   - Date: {$rep->date_created}\n\n";
    }
}

// Check recent mobile money transactions
echo "RECENT MOBILE MONEY ACTIVITY:\n";
$recentMM = DB::table('repayments')
    ->where('loan_id', 107)
    ->where('type', 2) // Mobile money
    ->whereDate('date_created', '>=', date('Y-m-d', strtotime('-24 hours')))
    ->orderBy('id', 'desc')
    ->get();

if ($recentMM->isEmpty()) {
    echo "   No mobile money payments in last 24 hours\n\n";
} else {
    echo "   Found {$recentMM->count()} mobile money payment(s) in last 24 hours:\n\n";
    foreach ($recentMM as $mm) {
        echo "   ID {$mm->id}: {$mm->amount} UGX - " . 
             ($mm->status == 1 ? 'APPROVED' : 'PENDING') . 
             " - Ref: {$mm->transaction_reference}\n";
    }
    echo "\n";
}

// Check for PENDING repayments
$pending = DB::table('repayments')
    ->where('loan_id', 107)
    ->where('status', 0)
    ->get();

if ($pending->isNotEmpty()) {
    echo "⚠️ FOUND {$pending->count()} PENDING REPAYMENT(S):\n\n";
    foreach ($pending as $p) {
        echo "   Repayment ID: {$p->id}\n";
        echo "   - Amount: {$p->amount} UGX\n";
        echo "   - Reference: {$p->transaction_reference}\n";
        echo "   - Date: {$p->date_created}\n";
        echo "   - Schedule ID: " . ($p->schedule_id ?? 'NULL ⚠️') . "\n\n";
        
        echo "   Would you like to manually approve this payment? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        
        if ($line == 'y') {
            echo "\n   Processing approval for repayment {$p->id}...\n";
            
            // Use RepaymentService to approve
            $repaymentService = app(\App\Services\RepaymentService::class);
            $result = $repaymentService->approveRepayment($p->id, '00', 'Manually approved - TEST');
            
            if ($result['success']) {
                echo "   ✅ SUCCESS! Payment approved\n";
                echo "   {$result['message']}\n\n";
                
                // Show updated status
                $updatedRep = DB::table('repayments')->find($p->id);
                $updatedSch = DB::table('loan_schedules')->find($p->schedule_id ?? 0);
                $updatedLoan = DB::table('personal_loans')->find(107);
                
                echo "   Updated Status:\n";
                echo "   - Repayment: " . ($updatedRep->status == 1 ? 'APPROVED ✓' : 'PENDING') . "\n";
                if ($updatedSch) {
                    echo "   - Schedule: " . ($updatedSch->status == 1 ? 'PAID ✓' : 'PENDING') . "\n";
                    echo "   - Schedule Paid: {$updatedSch->paid} UGX\n";
                }
                echo "   - Loan Status: " . ($updatedLoan->status == 3 ? 'CLOSED ✓' : 'ACTIVE') . "\n\n";
            } else {
                echo "   ❌ FAILED: {$result['message']}\n\n";
            }
        }
        fclose($handle);
    }
} else {
    echo "✓ No pending repayments\n\n";
}

// Summary
echo "=== SUMMARY ===\n\n";

$totalSchedules = $schedules->count();
$paidSchedules = $schedules->where('status', 1)->count();
$totalRepayments = $repayments->count();
$approvedRepayments = $repayments->where('status', 1)->count();
$pendingRepayments = $repayments->where('status', 0)->count();

echo "Schedules: {$paidSchedules}/{$totalSchedules} paid\n";
echo "Repayments: {$approvedRepayments} approved, {$pendingRepayments} pending\n";
echo "Loan Status: " . ($loan->status == 2 ? 'ACTIVE' : ($loan->status == 3 ? 'CLOSED' : 'OTHER')) . "\n\n";

if ($pendingRepayments > 0) {
    echo "⚠️ ACTION NEEDED: {$pendingRepayments} payment(s) waiting for callback or manual approval\n";
    echo "   Client may have paid but system didn't receive callback confirmation\n\n";
}

if ($repayments->isEmpty()) {
    echo "ℹ️ NO PAYMENTS RECORDED YET\n";
    echo "   If client says they paid:\n";
    echo "   1. Check if payment was initiated in admin panel\n";
    echo "   2. Check if client completed mobile money prompt\n";
    echo "   3. Check mobile money provider for transaction status\n\n";
}

echo "=== END DIAGNOSIS ===\n";
