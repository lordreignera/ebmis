<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Investment Accounts:\n";
echo str_repeat("=", 80) . "\n";

$investments = DB::table('investment')->get();

if ($investments->isEmpty()) {
    echo "No investment accounts found!\n";
} else {
    foreach($investments as $inv) {
        echo sprintf("ID: %d - Name: %s\n", 
            $inv->id, 
            $inv->name ?? 'N/A'
        );
    }
}
