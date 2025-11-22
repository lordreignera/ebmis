<?php
/**
 * Diagnose Isaac's Payment Issue
 * 
 * Check why schedule wasn't marked as paid and loan wasn't closed
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Diagnosing Isaac's Payment Issue (Loan 133) ===\n\n";

// Get loan details
$loan = DB::table('personal_loans')->where('id', 133)->first();

echo "LOAN STATUS:\n";
echo str_repeat('=', 80) . "\n";
echo "Loan ID: {$loan->id}\n";
echo "Loan Code: {$loan->code}\n";
echo "Principal: " . number_format($loan->principal, 2) . " UGX\n";
echo "Interest: " . number_format($loan->interest, 2) . " UGX\n";
echo "Status: {$loan->status} (0=Pending, 1=Approved, 2=Active, 3=Closed)\n";
echo "Date Closed: " . ($loan->date_closed ?? 'NULL') . "\n";
echo "\n";

// Get schedules
$schedules = DB::table('loan_schedules')
    ->where('loan_id', 133)
    ->orderBy('id')
    ->get();

echo "SCHEDULES:\n";
echo str_repeat('=', 80) . "\n";
echo sprintf("%-8s | %-10s | %-10s | %-10s | %-10s | %-8s | %s\n",
    'Sch ID', 'Principal', 'Interest', 'Payment', 'Paid', 'Balance', 'Status');
echo str_repeat('-', 80) . "\n";

$totalPayment = 0;
$totalPaid = 0;
$allPaid = true;

foreach ($schedules as $schedule) {
    $payment = floatval($schedule->payment ?? ($schedule->principal + $schedule->interest));
    $paid = floatval($schedule->paid ?? 0);
    $balance = $payment - $paid;
    
    $totalPayment += $payment;
    $totalPaid += $paid;
    
    if ($balance > 0.01) {
        $allPaid = false;
    }
    
    $statusText = $schedule->status == 1 ? '✓ PAID' : '✗ PENDING';
    if ($balance <= 0.01 && $schedule->status != 1) {
        $statusText = '⚠️  SHOULD BE PAID!';
    }
    
    echo sprintf("%-8s | %-10s | %-10s | %-10s | %-10s | %-10s | %s\n",
        $schedule->id,
        number_format($schedule->principal, 2),
        number_format($schedule->interest, 2),
        number_format($payment, 2),
        number_format($paid, 2),
        number_format($balance, 2),
        $statusText
    );
}

echo str_repeat('-', 80) . "\n";
echo sprintf("%-41s | %-10s | %-10s | %-10s\n",
    'TOTALS',
    number_format($totalPayment, 2),
    number_format($totalPaid, 2),
    number_format($totalPayment - $totalPaid, 2)
);
echo "\n";

// Get repayments
$repayments = DB::table('repayments')
    ->where('loan_id', 133)
    ->where('status', 1)
    ->orderBy('id', 'desc')
    ->get();

echo "REPAYMENTS:\n";
echo str_repeat('=', 80) . "\n";
echo sprintf("%-8s | %-10s | %-10s | %-12s | %s\n",
    'Rep ID', 'Sched ID', 'Amount', 'Date', 'Reference');
echo str_repeat('-', 80) . "\n";

foreach ($repayments as $repayment) {
    $ref = $repayment->transaction_reference ?? $repayment->txn_id ?? 'N/A';
    echo sprintf("%-8s | %-10s | %-10s | %-12s | %s\n",
        $repayment->id,
        $repayment->schedule_id ?? 'NULL',
        number_format($repayment->amount, 2),
        substr($repayment->date_created, 0, 10),
        substr($ref, 0, 20)
    );
}
echo "\n";

// Diagnostic checks
echo "DIAGNOSTIC CHECKS:\n";
echo str_repeat('=', 80) . "\n";

$issues = [];

// Check 1: Do schedules have 'payment' column?
$columns = DB::select("SHOW COLUMNS FROM loan_schedules WHERE Field = 'payment'");
if (empty($columns)) {
    $issues[] = "✗ 'payment' column missing in loan_schedules table!";
    echo "✗ ISSUE: 'payment' column missing in loan_schedules table\n";
    echo "   The code checks: \$schedule->payment - \$schedule->paid\n";
    echo "   But 'payment' column doesn't exist!\n";
    echo "   Should use: (\$schedule->principal + \$schedule->interest) instead\n";
} else {
    echo "✓ 'payment' column exists\n";
}

// Check 2: Do schedules have 'paid' column?
$columns = DB::select("SHOW COLUMNS FROM loan_schedules WHERE Field = 'paid'");
if (empty($columns)) {
    $issues[] = "✗ 'paid' column missing in loan_schedules table!";
    echo "✗ ISSUE: 'paid' column missing in loan_schedules table\n";
    echo "   The code increments: \$schedule->increment('paid', \$amount)\n";
    echo "   But 'paid' column doesn't exist!\n";
} else {
    echo "✓ 'paid' column exists\n";
}

// Check 3: Are the column values populated?
$firstSchedule = $schedules->first();
if ($firstSchedule) {
    echo "\n";
    echo "Sample Schedule (ID {$firstSchedule->id}) column values:\n";
    echo "  principal: " . ($firstSchedule->principal ?? 'NULL') . "\n";
    echo "  interest: " . ($firstSchedule->interest ?? 'NULL') . "\n";
    echo "  payment: " . ($firstSchedule->payment ?? 'NULL') . "\n";
    echo "  paid: " . ($firstSchedule->paid ?? 'NULL') . "\n";
    echo "  status: " . ($firstSchedule->status ?? 'NULL') . "\n";
    
    if (!isset($firstSchedule->payment) || $firstSchedule->payment === null) {
        $issues[] = "✗ 'payment' column is NULL - should be principal + interest";
        echo "\n⚠️  PROBLEM FOUND: 'payment' column is NULL!\n";
        echo "   This causes the balance check to fail\n";
        echo "   \$balance = \$schedule->payment - \$schedule->paid\n";
        echo "   \$balance = NULL - {$firstSchedule->paid}\n";
        echo "   Result: Schedule never marked as paid!\n";
    }
    
    if (!isset($firstSchedule->paid) || $firstSchedule->paid === null) {
        $issues[] = "✗ 'paid' column is NULL - should be total amount paid";
        echo "\n⚠️  PROBLEM FOUND: 'paid' column is NULL!\n";
        echo "   This means payments aren't being tracked\n";
    }
}

echo "\n";

// Show the fix
if (!empty($issues)) {
    echo "ROOT CAUSE:\n";
    echo str_repeat('=', 80) . "\n";
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
    echo "\n";
    
    echo "THE FIX:\n";
    echo str_repeat('=', 80) . "\n";
    echo "The checkAndCloseLoanIfComplete() function uses:\n";
    echo "  \$balance = \$schedule->payment - \$schedule->paid;\n";
    echo "\n";
    echo "But it should handle cases where 'payment' column is NULL:\n";
    echo "  \$payment = \$schedule->payment ?? (\$schedule->principal + \$schedule->interest);\n";
    echo "  \$paid = \$schedule->paid ?? 0;\n";
    echo "  \$balance = \$payment - \$paid;\n";
    echo "\n";
    echo "Also, the storeRepayment() function increments:\n";
    echo "  \$schedule->increment('paid', \$paymentAmount);\n";
    echo "\n";
    echo "But if 'paid' column is NULL initially, increment won't work properly.\n";
    echo "Should initialize it first or use update instead.\n";
    echo "\n";
} else {
    echo "✓ No column issues found\n";
    echo "\n";
    
    // Check if it's a logic issue
    echo "Checking payment logic:\n";
    foreach ($schedules as $schedule) {
        $payment = floatval($schedule->payment ?? ($schedule->principal + $schedule->interest));
        $paid = floatval($schedule->paid ?? 0);
        $balance = $payment - $paid;
        
        echo "\nSchedule {$schedule->id}:\n";
        echo "  Payment due: {$payment}\n";
        echo "  Amount paid: {$paid}\n";
        echo "  Balance: {$balance}\n";
        echo "  Status: " . ($schedule->status == 1 ? 'PAID' : 'PENDING') . "\n";
        
        if ($balance <= 0.01 && $schedule->status != 1) {
            echo "  ⚠️  Should be marked as PAID but isn't!\n";
        }
    }
}

echo "\n";
echo "SOLUTION:\n";
echo str_repeat('=', 80) . "\n";
echo "Run this SQL to manually fix Isaac's loan:\n\n";

foreach ($schedules as $schedule) {
    $payment = floatval($schedule->payment ?? ($schedule->principal + $schedule->interest));
    $paid = floatval($schedule->paid ?? 0);
    $balance = $payment - $paid;
    
    if ($balance <= 0.01 && $schedule->status != 1) {
        echo "UPDATE loan_schedules SET status = 1, date_cleared = NOW() WHERE id = {$schedule->id};\n";
    }
}

if ($allPaid) {
    echo "UPDATE personal_loans SET status = 3, date_closed = NOW() WHERE id = 133;\n";
}

echo "\n=== Diagnosis Complete ===\n";
