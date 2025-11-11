<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Investment Accounts:\n";
echo str_repeat("=", 80) . "\n";

$investments = DB::table('investment_acc')->get();

foreach($investments as $inv) {
    echo sprintf("ID: %d - %s (Balance: %s)\n", 
        $inv->id, 
        $inv->name, 
        $inv->balance ?? '0'
    );
}
