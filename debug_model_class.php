<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\Loan;

echo "=== CHECKING MODEL CLASS ===\n\n";

$loan = PersonalLoan::find(132);

echo "Query used: PersonalLoan::find(132)\n";
echo "Actual class: " . get_class($loan) . "\n";
echo "ID property type: " . gettype($loan->id) . "\n";
echo "ID value: " . $loan->id . "\n";
echo "getKey() method: " . $loan->getKey() . "\n";
echo "getAttribute('id'): " . $loan->getAttribute('id') . "\n";

echo "\n\n=== CHECKING IN COLLECTION ===\n";
$loans = PersonalLoan::where('id', 132)->get()->map(function($loan) {
    $loan->loan_type = 'personal';
    return $loan;
});

foreach ($loans as $loan) {
    echo "Class: " . get_class($loan) . "\n";
    echo "ID type: " . gettype($loan->id) . "\n";
    echo "ID value: ";
    var_dump($loan->id);
    echo "getKey(): " . $loan->getKey() . "\n";
}
