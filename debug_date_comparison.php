<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Debugging Schedule Comparison Issue ===\n\n";

// Get schedule 5990 (Jan 12)
$schedule5990 = DB::table('loan_schedules')->where('id', 5990)->first();
echo "Schedule 5990:\n";
echo "  ID: {$schedule5990->id}\n";
echo "  payment_date: {$schedule5990->payment_date}\n\n";

// Try to find earlier schedules using the EXACT query from the controller
$earlierSchedules = DB::table('loan_schedules')
    ->where('loan_id', 167)
    ->where('id', '!=', 5990)
    ->where('payment_date', '<', $schedule5990->payment_date)
    ->where('status', '!=', 1)
    ->orderBy('payment_date', 'asc')
    ->get();

echo "Earlier unpaid schedules found: " . $earlierSchedules->count() . "\n";
if ($earlierSchedules->count() > 0) {
    foreach ($earlierSchedules as $earlier) {
        echo "  - Schedule #{$earlier->id}: {$earlier->payment_date}\n";
    }
} else {
    echo "  (none)\n";
}

echo "\n";

// Now check schedule 5991
$schedule5991 = DB::table('loan_schedules')->where('id', 5991)->first();
echo "Schedule 5991:\n";
echo "  ID: {$schedule5991->id}\n";
echo "  payment_date: {$schedule5991->payment_date}\n\n";

// Check if 5990 is "earlier" than 5991
$is5990Earlier = DB::table('loan_schedules')
    ->where('loan_id', 167)
    ->where('id', '!=', 5991)
    ->where('payment_date', '<', $schedule5991->payment_date)
    ->where('id', 5990)
    ->exists();

echo "Is 5990 (Jan 12) earlier than 5991 (Jan 13)? " . ($is5990Earlier ? 'YES' : 'NO') . "\n";

echo "\n=== Complete ===\n";
