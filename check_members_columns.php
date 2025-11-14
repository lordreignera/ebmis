<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking members table columns:\n\n";

$columns = DB::select("SHOW COLUMNS FROM members");

foreach ($columns as $column) {
    echo "Column: {$column->Field}, Type: {$column->Type}\n";
}

echo "\n\nChecking a sample member record:\n";
$member = DB::table('members')->first();
if ($member) {
    echo "Sample member found\n";
    echo "Fields present:\n";
    foreach ($member as $key => $value) {
        echo "  {$key}: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . "\n";
    }
}
