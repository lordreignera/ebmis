<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking fees_types table columns:\n\n";

$columns = DB::select("SHOW COLUMNS FROM fees_types");

foreach ($columns as $column) {
    echo "Column: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}\n";
}
