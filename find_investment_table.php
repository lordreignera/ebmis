<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Investment-related tables:\n";
echo str_repeat("=", 80) . "\n";

$tables = DB::select('SHOW TABLES');

foreach($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if(stripos($tableName, 'invest') !== false) {
        echo $tableName . "\n";
    }
}

echo "\nChecking disbursements table for inv_id column:\n";
$columns = DB::select('SHOW COLUMNS FROM disbursements WHERE Field LIKE "%inv%"');
foreach($columns as $col) {
    echo "Column: " . $col->Field . " Type: " . $col->Type . "\n";
}
