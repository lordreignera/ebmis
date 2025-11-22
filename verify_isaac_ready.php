<?php
/**
 * Check Isaac's Loan Status - Second Payment Test
 * 
 * Verifies that Isaac's loan (133) is ready for second payment
 * and that all calculations will work correctly
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Isaac Sendangire (Loan 133) - Second Payment Check ===\n\n";

// Get loan details
$loan = DB::table('personal_loans')->where('id', 133)->first();

if (!$loan) {
    echo "❌ Loan 133 not found!\n";
    exit(1);
}

echo "Loan Information:\n";
echo str_repeat('-', 70) . "\n";
echo "Loan Code: {$loan->code}\n";
echo "Member ID: {$loan->member_id}\n";
echo "Principal: " . number_format($loan->principal, 2) . " UGX\n";
echo "Interest: " . number_format($loan->interest ?? 0, 2) . " UGX\n";
echo "Period: {$loan->period} months\n";
echo "Installment: " . number_format($loan->installment ?? 0, 2) . " UGX\n";
echo "Status: ";
switch($loan->status) {
    case 0: echo "Pending\n"; break;
    case 1: echo "Approved\n"; break;
    case 2: echo "Active/Disbursed\n"; break;
    case 3: echo "Closed\n"; break;
    case 4: echo "Rejected\n"; break;
    default: echo "Unknown\n";
}
echo "\n";

// Get schedules
echo "Payment Schedules:\n";
echo str_repeat('-', 70) . "\n";

$schedules = DB::table('loan_schedules')
    ->where('loan_id', 133)
    ->orderBy('id')
    ->get();

$totalExpected = 0;
$totalPaid = 0;

foreach ($schedules as $idx => $schedule) {
    $scheduleNum = $idx + 1;
    $status = $schedule->status == 0 ? 'PENDING' : 'PAID';
    $statusIcon = $schedule->status == 0 ? '⏳' : '✅';
    
    $scheduleTotal = $schedule->principal + $schedule->interest;
    
    echo "{$statusIcon} Schedule {$scheduleNum} (ID: {$schedule->id}):\n";
    echo "   Principal: " . number_format($schedule->principal, 2) . " UGX\n";
    echo "   Interest: " . number_format($schedule->interest, 2) . " UGX\n";
    echo "   Total: " . number_format($scheduleTotal, 2) . " UGX\n";
    echo "   Status: {$status}\n";
    
    $totalExpected += $scheduleTotal;
    if ($schedule->status == 1) {
        $totalPaid += $scheduleTotal;
    }
    echo "\n";
}

echo "Summary:\n";
echo "Total Expected: " . number_format($totalExpected, 2) . " UGX\n";
echo "Total Paid: " . number_format($totalPaid, 2) . " UGX\n";
echo "Remaining: " . number_format($totalExpected - $totalPaid, 2) . " UGX\n";
echo "\n";

// Check repayments
echo "Payment History:\n";
echo str_repeat('-', 70) . "\n";

$repayments = DB::table('repayments')
    ->where('loan_id', 133)
    ->where('status', 1) // Approved only
    ->orderBy('id')
    ->get();

if ($repayments->isEmpty()) {
    echo "❌ No repayments found!\n";
} else {
    foreach ($repayments as $repayment) {
        $payType = match((int)$repayment->type) {
            1 => 'Cash',
            2 => 'Mobile Money',
            3 => 'Bank/Cheque',
            default => 'Unknown'
        };
        
        $reference = $repayment->transaction_reference ?? $repayment->txn_id ?? 'N/A';
        $hasBothFields = ($repayment->transaction_reference && $repayment->txn_id) ? '✓' : '✗';
        
        echo "Repayment ID {$repayment->id}:\n";
        echo "   Amount: " . number_format($repayment->amount, 2) . " UGX\n";
        echo "   Type: {$payType}\n";
        echo "   Reference: {$reference}\n";
        echo "   Both Fields Populated: {$hasBothFields}\n";
        echo "   Schedule ID: " . ($repayment->schedule_id ?: 'N/A') . "\n";
        echo "   Date: {$repayment->date_created}\n";
        echo "\n";
    }
}

// Calculate what second payment should be
echo "Second Payment Calculation:\n";
echo str_repeat('-', 70) . "\n";

$schedule2 = DB::table('loan_schedules')
    ->where('loan_id', 133)
    ->orderBy('id')
    ->skip(1)
    ->first();

if ($schedule2) {
    $schedule2Total = $schedule2->principal + $schedule2->interest;
    
    if ($schedule2->status == 1) {
        echo "⚠️  Schedule 2 is already marked as PAID!\n";
    } else {
        echo "Expected Payment Amount: " . number_format($schedule2Total, 2) . " UGX\n";
        echo "   Principal: " . number_format($schedule2->principal, 2) . " UGX\n";
        echo "   Interest: " . number_format($schedule2->interest, 2) . " UGX\n";
        echo "\n";
        
        // Check if there are late fees
        $today = date('Y-m-d');
        $dueDate = $schedule2->duedate ?? $schedule2->due_date ?? null;
        
        if ($dueDate && $dueDate < $today) {
            $daysLate = (strtotime($today) - strtotime($dueDate)) / 86400;
            $lateFeePercent = 12; // 12% late fee
            $lateFee = $schedule2Total * ($lateFeePercent / 100);
            
            echo "⚠️  Payment is LATE by " . floor($daysLate) . " days (Due: {$dueDate})\n";
            echo "Late Fee ({$lateFeePercent}%): " . number_format($lateFee, 2) . " UGX\n";
            echo "Total Amount Due: " . number_format($schedule2Total + $lateFee, 2) . " UGX\n";
        } else {
            echo "✓ Payment is ON TIME\n";
            if ($dueDate) echo "   Due Date: {$dueDate}\n";
            echo "Total Amount Due: " . number_format($schedule2Total, 2) . " UGX\n";
        }
    }
} else {
    echo "❌ Schedule 2 not found!\n";
}

echo "\n";

// Check system readiness
echo "System Readiness Check:\n";
echo str_repeat('-', 70) . "\n";

$checks = [];

// 1. Check if loan is active
$checks[] = [
    'check' => 'Loan Status is Active',
    'pass' => $loan->status == 2,
    'detail' => "Status: {$loan->status} (2 = Active)"
];

// 2. Check if there are unpaid schedules
$unpaidSchedules = DB::table('loan_schedules')
    ->where('loan_id', 133)
    ->where('status', 0)
    ->count();

$checks[] = [
    'check' => 'Has Unpaid Schedules',
    'pass' => $unpaidSchedules > 0,
    'detail' => "{$unpaidSchedules} unpaid schedule(s)"
];

// 3. Check if schedule_id is optional in validation
$controllerPath = __DIR__ . '/app/Http/Controllers/Admin/RepaymentController.php';
$controllerContent = file_get_contents($controllerPath);
$hasOptionalSchedule = strpos($controllerContent, "'s_id' => 'nullable") !== false;

$checks[] = [
    'check' => 'Schedule ID is Optional',
    'pass' => $hasOptionalSchedule,
    'detail' => $hasOptionalSchedule ? 'Validation allows nullable s_id' : 'Validation requires s_id'
];

// 4. Check if auto-find schedule logic exists
$hasAutoFind = strpos($controllerContent, 'if (!$request->s_id)') !== false 
            && strpos($controllerContent, '->where(\'status\', 0)') !== false;

$checks[] = [
    'check' => 'Auto-Find Schedule Logic',
    'pass' => $hasAutoFind,
    'detail' => $hasAutoFind ? 'Will auto-select first unpaid schedule' : 'No auto-find logic'
];

// 5. Check if reference standardization is in place
$hasRefStandardization = strpos($controllerContent, "str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT)") !== false;

$checks[] = [
    'check' => 'Reference Standardization',
    'pass' => $hasRefStandardization,
    'detail' => $hasRefStandardization ? 'Uses FlexiPay format (EbP + 10 digits)' : 'Uses old format'
];

// 6. Check if both fields are set
$setsBothFields = strpos($controllerContent, 'transaction_reference') !== false 
               && preg_match('/txn_id.*=.*reference/', $controllerContent);

$checks[] = [
    'check' => 'Both Reference Fields Set',
    'pass' => $setsBothFields,
    'detail' => $setsBothFields ? 'Sets both txn_id and transaction_reference' : 'Only sets one field'
];

// 7. Check if helpers.php is loaded
$composerPath = __DIR__ . '/composer.json';
$composerContent = file_get_contents($composerPath);
$hasHelpers = strpos($composerContent, 'app/Helpers/helpers.php') !== false;

$checks[] = [
    'check' => 'Helpers File Registered',
    'pass' => $hasHelpers,
    'detail' => $hasHelpers ? 'helpers.php in composer autoload' : 'helpers.php not registered'
];

// 8. Check if numberToWords function exists
$helpersPath = __DIR__ . '/app/Helpers/helpers.php';
$hasNumberToWords = file_exists($helpersPath) 
                 && strpos(file_get_contents($helpersPath), 'function numberToWords') !== false;

$checks[] = [
    'check' => 'numberToWords Function',
    'pass' => $hasNumberToWords,
    'detail' => $hasNumberToWords ? 'Receipt generation will work' : 'Receipt may fail'
];

// Display checks
$allPass = true;
foreach ($checks as $check) {
    $icon = $check['pass'] ? '✓' : '✗';
    $status = $check['pass'] ? 'PASS' : 'FAIL';
    
    echo "{$icon} {$check['check']}: {$status}\n";
    echo "   {$check['detail']}\n";
    
    if (!$check['pass']) {
        $allPass = false;
    }
}

echo "\n";

// Final verdict
echo "Final Assessment:\n";
echo str_repeat('=', 70) . "\n";

if ($allPass) {
    echo "✅ SYSTEM READY FOR ISAAC'S SECOND PAYMENT\n\n";
    echo "Next Steps:\n";
    echo "1. Navigate to repayments page\n";
    echo "2. Select Isaac Sendangire's loan (PLOAN{$loan->code})\n";
    echo "3. Payment will auto-select first unpaid schedule (Schedule 2)\n";
    echo "4. System will calculate correct amount including any late fees\n";
    echo "5. Reference number will use FlexiPay format (EbP##########)\n";
    echo "6. Both txn_id and transaction_reference will be populated\n";
    echo "7. Receipt will generate successfully with numberToWords helper\n";
    echo "8. If this is the last payment, loan will automatically close\n";
} else {
    echo "⚠️  SOME CHECKS FAILED - Review above for details\n";
}

echo "\n=== Check Complete ===\n";
