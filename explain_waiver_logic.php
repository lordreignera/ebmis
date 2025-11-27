<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   EXPLAINING WHAT WE DID - WITH EXAMPLES\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$upgradeStart = Carbon::parse('2025-10-30');
$upgradeEnd = Carbon::parse('2025-11-27');

echo "Upgrade Period: Oct 30 - Nov 27 (29 days)\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "EXAMPLE 1: Richard Ariengu\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$richard = DB::table('loan_schedules as ls')
    ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->leftJoin('late_fees as lf', 'ls.id', '=', 'lf.schedule_id')
    ->where('m.fname', 'LIKE', '%Richard%')
    ->where('ls.payment_date', '<', $upgradeEnd)
    ->select(
        'ls.id as schedule_id',
        'ls.payment_date as due_date',
        'ls.principal',
        'ls.interest',
        'pl.code',
        'pl.period',
        'lf.amount as waived_amount',
        'lf.status',
        'lf.days_overdue as waived_days'
    )
    ->first();

if ($richard) {
    $dueDate = Carbon::parse($richard->due_date);
    $totalDaysOverdue = $dueDate->diffInDays($upgradeEnd);
    $daysBeforeUpgrade = $dueDate->diffInDays($upgradeStart);
    $daysInUpgrade = $upgradeStart->diffInDays($upgradeEnd) + 1;
    
    echo "Schedule Due: " . $dueDate->format('d-M-Y') . "\n";
    echo "Total Days Overdue: {$totalDaysOverdue} days\n\n";
    
    echo "BREAKDOWN:\n";
    echo "  Days overdue BEFORE upgrade: {$daysBeforeUpgrade} days\n";
    echo "  Days overdue DURING upgrade: {$daysInUpgrade} days ⬅️ ONLY THESE WAIVED\n";
    echo "  Total: {$totalDaysOverdue} days\n\n";
    
    $scheduleAmount = $richard->principal + $richard->interest;
    $periodType = $richard->period ?? '2';
    
    // Calculate TOTAL late fee (all days)
    $totalPeriods = ceil($totalDaysOverdue / 30);
    $totalLateFee = ($scheduleAmount * 0.06) * $totalPeriods;
    
    // Calculate UPGRADE late fee (upgrade days only)
    $upgradePeriods = ceil($daysInUpgrade / 30);
    $upgradeLateFee = ($scheduleAmount * 0.06) * $upgradePeriods;
    
    // Calculate BEFORE upgrade late fee
    $beforePeriods = ceil($daysBeforeUpgrade / 30);
    $beforeLateFee = ($scheduleAmount * 0.06) * $beforePeriods;
    
    echo "LATE FEE CALCULATION:\n\n";
    
    echo "1. Late fee from BEFORE upgrade ({$daysBeforeUpgrade} days = {$beforePeriods} periods):\n";
    echo "   = " . number_format($scheduleAmount, 0) . " × 6% × {$beforePeriods}\n";
    echo "   = " . number_format($beforeLateFee, 0) . " UGX ⬅️ CLIENT MUST PAY THIS\n\n";
    
    echo "2. Late fee from DURING upgrade ({$daysInUpgrade} days = {$upgradePeriods} periods):\n";
    echo "   = " . number_format($scheduleAmount, 0) . " × 6% × {$upgradePeriods}\n";
    echo "   = " . number_format($upgradeLateFee, 0) . " UGX ⬅️ WE WAIVED THIS\n\n";
    
    echo "3. TOTAL late fee (if no waiver):\n";
    echo "   = " . number_format($totalLateFee, 0) . " UGX\n\n";
    
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "RESULT:\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "✓ Client STILL PAYS: " . number_format($beforeLateFee, 0) . " UGX (old late fees)\n";
    echo "✓ We WAIVED: " . number_format($upgradeLateFee, 0) . " UGX (upgrade period)\n";
    echo "✓ Client SAVES: " . number_format($upgradeLateFee, 0) . " UGX\n\n";
    
    if ($richard->waived_amount) {
        echo "Actual waived amount in database: " . number_format($richard->waived_amount, 0) . " UGX\n";
        echo "Status: " . ($richard->status == 2 ? 'Waived ✓' : 'Pending') . "\n\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "EXAMPLE 2: BOSCO OKERENYANG\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$bosco = DB::table('loan_schedules as ls')
    ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
    ->where('ls.id', 5204)
    ->select('ls.payment_date as due_date', 'ls.principal', 'ls.interest')
    ->first();

if ($bosco) {
    $dueDate = Carbon::parse($bosco->due_date);
    $totalDaysOverdue = $dueDate->diffInDays($upgradeEnd);
    
    echo "Schedule Due: " . $dueDate->format('d-M-Y') . "\n";
    echo "Total Days Overdue: {$totalDaysOverdue} days\n\n";
    
    if ($dueDate->between($upgradeStart, $upgradeEnd)) {
        echo "BREAKDOWN:\n";
        echo "  Days overdue BEFORE upgrade: 0 days (due date was during upgrade)\n";
        echo "  Days overdue DURING upgrade: {$totalDaysOverdue} days ⬅️ ALL WAIVED\n\n";
        
        $scheduleAmount = $bosco->principal + $bosco->interest;
        $periods = ceil($totalDaysOverdue / 30);
        $lateFee = ($scheduleAmount * 0.06) * $periods;
        
        echo "LATE FEE CALCULATION:\n";
        echo "  = " . number_format($scheduleAmount, 0) . " × 6% × {$periods}\n";
        echo "  = " . number_format($lateFee, 0) . " UGX ⬅️ ALL WAIVED\n\n";
        
        echo "═══════════════════════════════════════════════════════════════════\n";
        echo "RESULT:\n";
        echo "═══════════════════════════════════════════════════════════════════\n\n";
        
        echo "✓ Client PAYS: 0 UGX late fees (all during upgrade)\n";
        echo "✓ We WAIVED: " . number_format($lateFee, 0) . " UGX (100% waived)\n";
        echo "✓ Client SAVES: " . number_format($lateFee, 0) . " UGX\n\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "SUMMARY - WHAT WE DID\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "✓ If schedule was due BEFORE upgrade:\n";
echo "    - Old late fees STILL EXIST (client must pay)\n";
echo "    - Only upgrade days waived\n\n";

echo "✓ If schedule was due DURING upgrade:\n";
echo "    - ALL late fees waived (nothing before upgrade)\n\n";

echo "✓ If schedule was due AFTER upgrade:\n";
echo "    - Not affected at all\n\n";

echo "WE DID NOT REMOVE ALL LATE FEES!\n";
echo "We only removed the fees from the 29-day upgrade period.\n\n";
