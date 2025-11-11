<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Approved Loans ===\n\n";

// Personal Loans
$personalLoans = \App\Models\PersonalLoan::where('status', 1)->get();
echo "Personal Loans (status=1): " . $personalLoans->count() . "\n";
foreach ($personalLoans->take(10) as $loan) {
    echo "  {$loan->code} - charge_type: " . ($loan->charge_type ?? 'NULL') . "\n";
}

echo "\n";

// Group Loans
$groupLoans = \App\Models\GroupLoan::where('status', 1)->get();
echo "Group Loans (status=1): " . $groupLoans->count() . "\n";
foreach ($groupLoans->take(10) as $loan) {
    echo "  {$loan->code} - charge_type: " . ($loan->charge_type ?? 'NULL') . "\n";
}

echo "\n=== Total Approved: " . ($personalLoans->count() + $groupLoans->count()) . " ===\n";
