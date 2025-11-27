<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   VERIFICATION - LOAN #105 (BOSCO OKERENYANG)\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$fourWeeksAgo = Carbon::now()->subWeeks(4);
$today = Carbon::now();

echo "Today: " . $today->format('d-m-Y') . "\n";
echo "Upgrade Period Start: " . $fourWeeksAgo->format('d-m-Y') . "\n\n";

// Get loan and schedules
$loan = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->where('pl.id', 105)
    ->select('pl.*', 'p.period_type', 'p.name as product_name', 'm.fname', 'm.lname')
    ->first();

$schedules = DB::table('loan_schedules')
    ->where('loan_id', 105)
    ->orderBy('payment_date')
    ->get();

echo "Borrower: {$loan->fname} {$loan->lname}\n";
echo "Loan Code: {$loan->code}\n";
echo "Product: {$loan->product_name}\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "SCHEDULES & LATE FEE ANALYSIS:\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

foreach ($schedules as $schedule) {
    $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
    $isPaid = $schedule->status == 1;
    $isOverdue = $dueDate->isPast() && !$isPaid;
    $inUpgradePeriod = $dueDate->between($fourWeeksAgo, $today);
    
    echo "Schedule #{$schedule->id}\n";
    echo "  Due Date: {$schedule->payment_date}\n";
    echo "  Amount: " . number_format($schedule->payment, 0) . " UGX\n";
    echo "  Status: " . ($isPaid ? "PAID ✓" : "UNPAID") . "\n";
    
    if ($isOverdue) {
        // Match EXACT calculation from RepaymentController
        $now = time();
        $your_date = strtotime($schedule->payment_date);
        $datediff = $now - $your_date;
        $d = floor($datediff / (60 * 60 * 24)); // Days overdue
        
        $dd = 0; // Periods overdue
        if ($d > 0) {
            if ($loan->period_type == '1') {
                $dd = ceil($d / 7); // Weekly
            } else if ($loan->period_type == '2') {
                $dd = ceil($d / 30); // Monthly
            } else if ($loan->period_type == '3') {
                $dd = $d; // Daily
            } else {
                $dd = ceil($d / 7); // Default weekly
            }
        }
        
        $daysOverdue = $d;
        $periodsOverdue = $dd;
        $scheduleAmount = $schedule->principal + $schedule->interest;
        $lateFee = ($scheduleAmount * 0.06) * $periodsOverdue;
        
        echo "  ⚠ OVERDUE: {$daysOverdue} days ({$periodsOverdue} periods)\n";
        echo "  💰 Late Fee: " . number_format($lateFee, 0) . " UGX\n";
        
        if ($inUpgradePeriod) {
            echo "  ✓ DUE DURING UPGRADE PERIOD - WILL BE WAIVED\n";
        } else {
            echo "  ⨯ Not in upgrade period - late fee remains\n";
        }
    } else if ($dueDate->isFuture()) {
        echo "  ℹ Future schedule - no late fee\n";
    }
    
    echo "\n";
}

// Check if late fees already recorded
$existingLateFees = DB::table('late_fees')
    ->where('loan_id', 105)
    ->get();

echo "─────────────────────────────────────────────────────────────────────\n";
echo "CURRENT LATE_FEES TABLE STATUS:\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

if ($existingLateFees->count() > 0) {
    echo "Found {$existingLateFees->count()} late fee record(s):\n\n";
    foreach ($existingLateFees as $lf) {
        $statusName = ['Pending', 'Paid', 'Waived', 'Cancelled'][$lf->status] ?? 'Unknown';
        echo "Late Fee #{$lf->id}\n";
        echo "  Schedule: #{$lf->schedule_id}\n";
        echo "  Amount: " . number_format($lf->amount, 0) . " UGX\n";
        echo "  Due Date: {$lf->schedule_due_date}\n";
        echo "  Status: {$statusName}\n";
        if ($lf->status == 2) {
            echo "  Waiver Reason: {$lf->waiver_reason}\n";
        }
        echo "\n";
    }
} else {
    echo "No late fees recorded yet.\n";
    echo "Run 'calculate_late_fees.php' to populate.\n\n";
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   SUMMARY FOR BOSCO OKERENYANG\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$overdueInPeriod = $schedules->filter(function($s) use ($fourWeeksAgo, $today) {
    try {
        $dueDate = Carbon::createFromFormat('d-m-Y', $s->payment_date);
        return $s->status == 0 && $dueDate->isPast() && $dueDate->between($fourWeeksAgo, $today);
    } catch (\Exception $e) {
        return false;
    }
});

if ($overdueInPeriod->count() > 0) {
    echo "✓ Has {$overdueInPeriod->count()} schedule(s) that qualify for waiver\n";
    echo "✓ These schedules were due during the 4-week upgrade period\n";
    echo "✓ Late fees for these will be waived\n";
    echo "✓ Client should pay original schedule amounts only\n";
} else {
    echo "• No schedules due during upgrade period\n";
    echo "• No late fee waiver applies\n";
}

echo "\n";
