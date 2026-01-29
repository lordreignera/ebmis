<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   CLEAR INCORRECT date_cleared VALUES                         ║\n";
echo "║   (Schedules without actual payment records)                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Find paid schedules with date_cleared but no actual payment record
$schedulesWithoutPayments = DB::table('loan_schedules as ls')
    ->leftJoin('repayments as r', function($join) {
        $join->on('r.schedule_id', '=', 'ls.id')
             ->where('r.status', '=', 1);
    })
    ->where('ls.status', 1)
    ->whereNotNull('ls.date_cleared')
    ->whereNull('r.id')
    ->select('ls.id', 'ls.loan_id', 'ls.paid', 'ls.payment', 'ls.date_cleared')
    ->get();

echo "Found " . $schedulesWithoutPayments->count() . " schedule(s) with date_cleared but no payment records\n\n";

if ($schedulesWithoutPayments->count() > 0) {
    echo "Examples (first 10):\n";
    foreach ($schedulesWithoutPayments->take(10) as $sched) {
        echo "  - Schedule #{$sched->id} | Loan {$sched->loan_id} | Paid: {$sched->paid}/{$sched->payment} | date_cleared: {$sched->date_cleared}\n";
    }
    
    echo "\nClearing date_cleared for these schedules...\n";
    
    $scheduleIds = $schedulesWithoutPayments->pluck('id')->toArray();
    
    $updated = DB::table('loan_schedules')
        ->whereIn('id', $scheduleIds)
        ->update(['date_cleared' => null]);
    
    echo "✓ Cleared date_cleared for {$updated} schedule(s)\n\n";
} else {
    echo "✓ No schedules found with incorrect date_cleared\n\n";
}

echo "✓ Complete!\n\n";
