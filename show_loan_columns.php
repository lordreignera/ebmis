<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "PERSONAL_LOANS TABLE COLUMNS\n";
echo "========================================\n\n";

$cols = DB::select('SHOW COLUMNS FROM personal_loans');
foreach($cols as $col) {
    if(strpos($col->Field, 'period') !== false || strpos($col->Field, 'product') !== false) {
        echo $col->Field . " - " . $col->Type . "\n";
    }
}

echo "\n\nChecking Loan #105:\n";
$loan = DB::select('SELECT * FROM personal_loans WHERE id = 105')[0];
echo "  period: " . ($loan->period ?? 'NULL') . "\n";
echo "  products_id: " . ($loan->products_id ?? 'NULL') . "\n";
