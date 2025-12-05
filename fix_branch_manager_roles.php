<?php
/**
 * Fix Branch Manager Roles for Production Database
 * This script assigns the "Branch Manager" role to all users with user_type = 'branch'
 * 
 * Usage: php fix_branch_manager_roles.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "=================================================\n";
echo "Fix Branch Manager Roles Script\n";
echo "=================================================\n\n";

try {
    // Step 1: Check if Branch Manager role exists
    echo "Step 1: Checking if Branch Manager role exists...\n";
    $branchManagerRole = Role::where('name', 'Branch Manager')->first();
    
    if (!$branchManagerRole) {
        echo "❌ ERROR: Branch Manager role not found!\n";
        echo "Run: php artisan db:seed --class=RolesSeeder\n";
        exit(1);
    }
    
    echo "✅ Branch Manager role found (ID: {$branchManagerRole->id})\n\n";
    
    // Step 2: Find all branch users without the role
    echo "Step 2: Finding branch users without Branch Manager role...\n";
    $branchUsers = User::where('user_type', 'branch')
        ->whereDoesntHave('roles', function($query) {
            $query->where('name', 'Branch Manager');
        })
        ->get();
    
    if ($branchUsers->isEmpty()) {
        echo "✅ All branch users already have the Branch Manager role!\n";
        echo "\nNothing to fix.\n";
        exit(0);
    }
    
    echo "Found {$branchUsers->count()} branch users without the role:\n";
    foreach ($branchUsers as $user) {
        echo "  - {$user->name} ({$user->email})\n";
    }
    echo "\n";
    
    // Step 3: Assign the role
    echo "Step 3: Assigning Branch Manager role...\n";
    $count = 0;
    foreach ($branchUsers as $user) {
        $user->assignRole('Branch Manager');
        $count++;
        echo "  ✅ Assigned to: {$user->name}\n";
    }
    
    echo "\n✅ Successfully assigned Branch Manager role to {$count} users!\n\n";
    
    // Step 4: Verify the results
    echo "Step 4: Verification - All branch users with roles:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-25s %-30s %-20s\n", "ID", "Name", "Email", "Roles");
    echo str_repeat("-", 80) . "\n";
    
    $allBranchUsers = User::where('user_type', 'branch')
        ->with('roles')
        ->orderBy('id')
        ->get();
    
    foreach ($allBranchUsers as $user) {
        $roles = $user->roles->pluck('name')->join(', ');
        printf("%-5s %-25s %-30s %-20s\n", 
            $user->id, 
            substr($user->name, 0, 25), 
            substr($user->email, 0, 30),
            substr($roles, 0, 20)
        );
    }
    
    echo str_repeat("-", 80) . "\n";
    echo "\n✅ Done! All branch users now have the Branch Manager role.\n";
    echo "They can now upload documents and access all EBIMS modules.\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
