<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Debugging Schedule Payment Order ===\n\n";

$schedules = DB::table('loan_schedules')
    ->where('loan_id', 167)
    ->orderBy('id')
    ->get();

foreach ($schedules as $schedule) {
    echo "Schedule #{$schedule->id}:\n";
    echo "  payment_date: {$schedule->payment_date}\n";
    echo "  payment: {$schedule->payment}\n";
    echo "  status: {$schedule->status}\n";
    
    // Check if there are earlier unpaid schedules
    $earlier = DB::table('loan_schedules')
        ->where('loan_id', 167)
        ->where('id', '!=', $schedule->id)
        ->where('payment_date', '<', $schedule->payment_date)
        ->where('status', '!=', 1)
        ->orderBy('payment_date', 'asc')
        ->get();
    
    if ($earlier->count() > 0) {
        echo "  Earlier unpaid schedules:\n";
        foreach ($earlier as $e) {
            echo "    - #{$e->id}: {$e->payment_date}\n";
        }
    } else {
        echo "  No earlier unpaid schedules\n";
    }
    
    echo "\n";
}

// Test string comparison
echo "String comparison test:\n";
echo "'12-01-2026' < '13-01-2026': " . (('12-01-2026' < '13-01-2026') ? 'TRUE' : 'FALSE') . "\n";
echo "'13-01-2026' < '12-01-2026': " . (('13-01-2026' < '12-01-2026') ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== Complete ===\n";
