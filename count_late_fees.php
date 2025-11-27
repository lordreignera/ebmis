<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$count = DB::table('late_fees')->count();
echo "Late fees records: {$count}\n";

$pending = DB::table('late_fees')->where('status', 0)->count();
echo "Pending: {$pending}\n";

$waived = DB::table('late_fees')->where('status', 2)->count();
echo "Waived: {$waived}\n";

$total = DB::table('late_fees')->sum('amount');
echo "Total amount: " . number_format($total, 0) . " UGX\n";
