<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "repayments table structure:\n";
echo str_repeat("=", 80) . "\n";

$columns = DB::select('SHOW COLUMNS FROM repayments');

foreach ($columns as $column) {
    echo sprintf("%-30s %-20s %-10s %-10s\n", 
        $column->Field, 
        $column->Type, 
        $column->Null,
        $column->Default ?? ''
    );
}

echo "\n\nSample repayment record:\n";
echo str_repeat("=", 80) . "\n";

$sample = DB::table('repayments')->orderBy('id', 'desc')->first();
if ($sample) {
    foreach ($sample as $key => $value) {
        echo sprintf("%-30s: %s\n", $key, $value ?? 'NULL');
    }
}
