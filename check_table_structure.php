<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "System Accounts Table Structure:\n\n";

// Get all system accounts
$accounts = DB::table('system_accounts')->select('id', 'code', 'name', 'status')->get();

echo "Total accounts: " . $accounts->count() . "\n\n";

// Show accounts with IDs 37-45
echo "Accounts with IDs 37-45:\n";
foreach ($accounts as $account) {
    if ($account->id >= 37 && $account->id <= 45) {
        echo "ID: {$account->id}, Code: {$account->code}, Name: {$account->name}, Status: {$account->status}\n";
    }
}

echo "\n\nFees Types Table:\n\n";
$feeTypes = DB::table('fees_types')->select('id', 'name', 'account')->get();

foreach ($feeTypes as $ft) {
    echo "ID: {$ft->id}, Name: {$ft->name}, Account: {$ft->account}\n";
}
