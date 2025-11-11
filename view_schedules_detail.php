<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Loan 132 - Detailed Schedule Information:\n";
echo str_repeat("=", 80) . "\n\n";

$schedules = DB::table('loan_schedules')
    ->where('loan_id', 132)
    ->orderBy('id')
    ->get();

foreach ($schedules as $schedule) {
    echo "Schedule ID: " . $schedule->id . "\n";
    echo "  Payment Date: " . $schedule->payment_date . "\n";
    echo "  Principal: " . $schedule->principal . "\n";
    echo "  Interest: " . $schedule->interest . "\n";
    echo "  Total Payment: " . $schedule->payment . "\n";
    echo "  Balance After: " . $schedule->balance . "\n";
    echo "  Status: " . ($schedule->status == 0 ? 'Pending' : 'Paid') . "\n";
    echo str_repeat("-", 80) . "\n";
}

echo "\nTotal Schedules: " . count($schedules) . "\n";
