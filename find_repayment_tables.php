<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Repayment-related tables:\n";
echo str_repeat("=", 80) . "\n";

$tables = DB::select('SHOW TABLES');

foreach($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if(stripos($tableName, 'pay') !== false || stripos($tableName, 'schedule') !== false || stripos($tableName, 'collection') !== false) {
        echo $tableName . "\n";
    }
}
