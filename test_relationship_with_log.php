<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FeeType;
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

echo "Testing FeeType->systemAccount relationship with query log:\n\n";

$feeType = FeeType::with('systemAccount')->first();

$queries = DB::getQueryLog();

echo "Queries executed:\n";
foreach ($queries as $query) {
    echo $query['query'] . "\n";
    print_r($query['bindings']);
    echo "\n";
}

echo "\nFee Type: {$feeType->name}\n";
echo "Account ID: {$feeType->account}\n";
echo "Account ID type: " . gettype($feeType->account) . "\n";

if ($feeType->systemAccount) {
    echo "✓ System Account: {$feeType->systemAccount->code} - {$feeType->systemAccount->name}\n";
} else {
    echo "✗ System Account is null\n";
}
