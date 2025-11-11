<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "raw_payments table structure:\n";
echo str_repeat("=", 80) . "\n";

$columns = DB::select('SHOW COLUMNS FROM raw_payments');

foreach ($columns as $column) {
    echo sprintf("%-30s %-20s %-10s\n", 
        $column->Field, 
        $column->Type, 
        $column->Null
    );
}
