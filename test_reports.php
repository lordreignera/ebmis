<?php

// Simple test script to debug the reports issue
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PersonalLoan;
use Carbon\Carbon;

echo "Testing PersonalLoan disbursed query...\n";

try {
    // Test the query that the report will use
    $startDate = '01/01/2020';
    $endDate = '31/12/2025';
    $type = '1';

    echo "Testing date conversion:\n";
    echo "Start: " . Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d') . "\n";
    echo "End: " . Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d') . "\n";

    echo "\nQuerying disbursed personal loans...\n";
    $query = PersonalLoan::query()
        ->where('verified', 1)
        ->whereBetween('datecreated', [
            Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d'),
            Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d')
        ]);

    $count = $query->count();
    echo "Found {$count} disbursed personal loans\n";

    if ($count > 0) {
        echo "\nTesting relationships...\n";
        $loans = $query->with(['member', 'branch', 'product'])->limit(1)->get();
        $loan = $loans->first();
        
        echo "Loan ID: " . $loan->id . "\n";
        echo "Member: " . ($loan->member ? $loan->member->fname . ' ' . $loan->member->lname : 'N/A') . "\n";
        echo "Branch: " . ($loan->branch ? $loan->branch->name : 'N/A') . "\n";
        echo "Product: " . ($loan->product ? $loan->product->name : 'N/A') . "\n";
    }

    echo "\nTest completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}