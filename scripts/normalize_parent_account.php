<?php
// Normalize parent_account values: convert 0 to NULL
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$affected = DB::table('system_accounts')->where('parent_account', 0)->update(['parent_account' => null]);

echo "Updated rows: $affected\n";
