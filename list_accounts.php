<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$rows = DB::table('system_accounts')->orderBy('code')->orderBy('sub_code')->get(['Id','code','sub_code','name']);
foreach ($rows as $r) {
    echo $r->Id."\t".$r->code."\t".$r->sub_code."\t".$r->name."\n";
}
