<?php
// Dump system_accounts for inspection
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SystemAccount;

$accounts = SystemAccount::with('parent')
    ->orderBy('code')
    ->orderBy('sub_code')
    ->get(['Id','code','sub_code','parent_account','name'])
    ->map(function($a){
        return [
            'Id' => $a->Id,
            'code' => $a->code,
            'sub_code' => $a->sub_code,
            'parent_account' => $a->parent_account,
            'parent_name' => $a->parent ? $a->parent->name : null,
            'name' => $a->name,
        ];
    });

echo json_encode([
    'count' => $accounts->count(),
    'rows' => $accounts
], JSON_PRETTY_PRINT);
