<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->boot();

try {
    echo "Testing esign query fix...\n";
    
    // Test individual queries first
    $personalCount = \App\Models\PersonalLoan::where('is_esign', true)->count();
    echo "Personal esign loans: $personalCount\n";
    
    $groupCount = \App\Models\GroupLoan::where('is_esign', true)->count();
    echo "Group esign loans: $groupCount\n";
    
    // Test the unified query with specific columns (like in our controller fix)
    $commonColumns = [
        'id',
        'code', 
        'product_type',
        'interest',
        'period', 
        'principal',
        'status',
        'verified',
        'added_by',
        'datecreated',
        'branch_id',
        'comments',
        'charge_type',
        'date_closed'
    ];
    
    $personalLoans = \App\Models\PersonalLoan::where('is_esign', true)
                     ->select(array_merge($commonColumns, [
                         'member_id',
                         \Illuminate\Support\Facades\DB::raw("'personal' as loan_type"),
                         \Illuminate\Support\Facades\DB::raw("NULL as group_id")
                     ]));

    $groupLoans = \App\Models\GroupLoan::where('is_esign', true)
                  ->select(array_merge($commonColumns, [
                      'group_id',
                      \Illuminate\Support\Facades\DB::raw("'group' as loan_type"),
                      \Illuminate\Support\Facades\DB::raw("NULL as member_id")
                  ]));

    // Test the union query
    $loans = $personalLoans->union($groupLoans)->get();
    echo "Union query successful! Found " . $loans->count() . " total esign loans\n";
    
    // Test pagination query
    $paginatedLoans = $personalLoans->union($groupLoans)->paginate(20);
    echo "Pagination query successful! Found " . $paginatedLoans->total() . " total esign loans\n";
    
    echo "All tests passed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}