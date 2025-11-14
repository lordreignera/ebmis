<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Running the exact query that Eloquent uses:\n\n";

$results = DB::select('select * from `system_accounts` where `system_accounts`.`id` in (38)');

echo "Number of results: " . count($results) . "\n\n";

if (count($results) > 0) {
    echo "Results found:\n";
    foreach ($results as $result) {
        print_r($result);
    }
} else {
    echo "No results found!\n";
}

echo "\n\nTrying with where id = 38:\n";
$results2 = DB::select('select * from `system_accounts` where `id` = 38');
echo "Number of results: " . count($results2) . "\n";
if (count($results2) > 0) {
    print_r($results2[0]);
}
