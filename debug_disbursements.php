<?php
/**
 * Quick diagnostic script for disbursement issues
 * Run: php artisan tinker --execute="require_once 'debug_disbursements.php';"
 */

echo "=== Disbursement Debug Report ===\n\n";

try {
    // Check personal loans pending disbursement
    $personalLoans = \App\Models\PersonalLoan::where('status', 1)
        ->whereDoesntHave('disbursements', function($q) {
            $q->where('status', 1);
        })
        ->with(['member', 'product', 'branch'])
        ->get();
        
    echo "Personal Loans Pending Disbursement: " . $personalLoans->count() . "\n";
    
    if ($personalLoans->count() > 0) {
        echo "Sample Personal Loan:\n";
        $loan = $personalLoans->first();
        echo "  - Code: " . ($loan->code ?? 'NULL') . "\n";
        echo "  - Member: " . ($loan->member->fname ?? 'NULL') . " " . ($loan->member->lname ?? 'NULL') . "\n";
        echo "  - Principal: " . ($loan->principal ?? 'NULL') . "\n";
        echo "  - Product: " . ($loan->product->name ?? 'NULL') . "\n";
        echo "  - Branch: " . ($loan->branch->name ?? 'NULL') . "\n";
    }
    
    // Check group loans pending disbursement
    $groupLoans = \App\Models\GroupLoan::where('status', 1)
        ->whereDoesntHave('disbursements', function($q) {
            $q->where('status', 1);
        })
        ->with(['group', 'product', 'branch'])
        ->get();
        
    echo "\nGroup Loans Pending Disbursement: " . $groupLoans->count() . "\n";
    
    if ($groupLoans->count() > 0) {
        echo "Sample Group Loan:\n";
        $loan = $groupLoans->first();
        echo "  - Code: " . ($loan->code ?? 'NULL') . "\n";
        echo "  - Group: " . ($loan->group->group_name ?? 'NULL') . "\n";
        echo "  - Principal: " . ($loan->principal ?? 'NULL') . "\n";
        echo "  - Product: " . ($loan->product->name ?? 'NULL') . "\n";
        echo "  - Branch: " . ($loan->branch->name ?? 'NULL') . "\n";
    }
    
    // Check disbursements table
    $disbursements = \App\Models\Disbursement::count();
    echo "\nTotal Disbursements in DB: " . $disbursements . "\n";
    
    // Check if there are any relationship issues
    echo "\n=== Checking for Data Issues ===\n";
    
    // Personal loans without members
    $orphanPersonalLoans = \App\Models\PersonalLoan::whereDoesntHave('member')->count();
    echo "Personal loans without members: " . $orphanPersonalLoans . "\n";
    
    // Personal loans without products
    $orphanProductLoans = \App\Models\PersonalLoan::whereDoesntHave('product')->count();
    echo "Personal loans without products: " . $orphanProductLoans . "\n";
    
    // Personal loans without branches
    $orphanBranchLoans = \App\Models\PersonalLoan::whereDoesntHave('branch')->count();
    echo "Personal loans without branches: " . $orphanBranchLoans . "\n";
    
    // Check if pagination is working
    echo "\n=== Testing Pagination ===\n";
    $paginatedLoans = \App\Models\PersonalLoan::where('status', 1)->paginate(20);
    echo "Paginated loans total: " . $paginatedLoans->total() . "\n";
    echo "Paginated loans per page: " . $paginatedLoans->perPage() . "\n";
    echo "Paginated loans current page: " . $paginatedLoans->currentPage() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== End Debug Report ===\n";
?>