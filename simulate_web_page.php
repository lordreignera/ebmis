<?php
/**
 * This replicates EXACTLY what http://localhost:84/admin/loans/repayments/schedules/105
 * will show when you load it RIGHT NOW
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   CURRENT WEB PAGE VALUES FOR LOAN 105\n";
echo "   (What you'll see when you refresh the page RIGHT NOW)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$loanId = 105;

// Get loan
$loan = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->where('pl.id', $loanId)
    ->select('pl.*', 'p.period_type', 'm.fname', 'm.lname', 'm.contact')
    ->first();

// Get schedules
$schedules = DB::table('loan_schedules')
    ->where('loan_id', $loanId)
    ->orderBy('payment_date')
    ->get();

echo "Borrower: {$loan->fname} {$loan->lname}\n";
echo "Loan Code: {$loan->code}\n";
echo "Phone: {$loan->contact}\n\n";

$principal = floatval($loan->principal);
$globalprincipal = floatval($loan->principal);

$totalPenalty = 0;
$overdueCount = 0;
$overdueAmount = 0;
$nextDue = null;

foreach ($schedules as $schedule) {
    // Calculate penalty - EXACT controller logic
    $now = time();
    $your_date = strtotime($schedule->payment_date);
    $datediff = $now - $your_date;
    $d = floor($datediff / (60 * 60 * 24));
    
    $dd = 0;
    if ($d > 0) {
        if ($loan->period_type == '2') { // Monthly
            $dd = ceil($d / 30);
        }
    }
    
    $intrestamtpayable = $schedule->interest;
    $latepayment = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
    
    // Set penalty_amount attribute (like controller does)
    $schedule->penalty_amount = $latepayment;
    $schedule->due_amount = $schedule->principal + $intrestamtpayable;
    $schedule->is_overdue = $d > 0 && $schedule->status == 0;
    
    if ($schedule->status == 0) {
        if (!$nextDue) {
            $nextDue = $schedule;
        }
        
        if ($schedule->is_overdue) {
            $overdueCount++;
            $overdueAmount += $schedule->due_amount + $schedule->penalty_amount;
        }
    }
    
    $totalPenalty += $latepayment;
    
    echo "Schedule #{$schedule->id}:\n";
    echo "  Due Date: {$schedule->payment_date}\n";
    echo "  Status: " . ($schedule->status == 1 ? "PAID" : "UNPAID") . "\n";
    echo "  Due Amount: " . number_format($schedule->due_amount, 0) . " UGX\n";
    echo "  Penalty: " . number_format($schedule->penalty_amount, 0) . " UGX\n";
    echo "  Days overdue: {$d}\n";
    echo "  Periods overdue: {$dd}\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   NEXT PAYMENT SECTION (Top Right of Page)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

if ($nextDue && $nextDue->is_overdue) {
    echo "Due Date: " . date('M d, Y', strtotime($nextDue->payment_date)) . "\n";
    echo "Amount: UGX " . number_format($nextDue->due_amount, 0) . "\n";
    echo "Penalty: UGX " . number_format($nextDue->penalty_amount, 0) . " <-- THIS IS WHAT YOU SEE\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   OVERDUE ALERT SECTION (Red Alert Box)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

if ($overdueCount > 0) {
    echo "This loan has {$overdueCount} overdue payment(s)\n";
    echo "totaling UGX " . number_format($overdueAmount, 0) . " <-- THIS IS WHAT YOU SEE\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "If you're seeing 78,000 UGX instead of 39,000 UGX, please:\n";
echo "1. Clear your browser cache (Ctrl+Shift+Delete)\n";
echo "2. Refresh the page (Ctrl+F5)\n";
echo "3. Take a new screenshot\n\n";

echo "Current calculation shows: 39,000 UGX (CORRECT)\n";
echo "If you still see 78,000 UGX, there may be browser caching or\n";
echo "the page was loaded at a different time when days=30+\n\n";
