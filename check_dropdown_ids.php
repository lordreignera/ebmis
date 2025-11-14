<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking system accounts for dropdown:\n\n";

$systemAccounts = \App\Models\SystemAccount::where('status', 1)->orderBy('code')->take(5)->get();

foreach ($systemAccounts as $acc) {
    echo "ID (via ->id): {$acc->id}\n";
    echo "ID (via ->Id): {$acc->Id}\n";
    echo "ID (via getKey()): {$acc->getKey()}\n";
    echo "Code: {$acc->code}\n";
    echo "Name: {$acc->name}\n";
    echo "---\n";
}

echo "\nChecking a fee type:\n";
$feeType = \App\Models\FeeType::first();
echo "Fee Type: {$feeType->name}\n";
echo "Account field value: {$feeType->account}\n";
echo "Account type: " . gettype($feeType->account) . "\n";

if ($feeType->systemAccount) {
    echo "Related SystemAccount ID: {$feeType->systemAccount->id}\n";
    echo "Related SystemAccount Id: {$feeType->systemAccount->Id}\n";
}
