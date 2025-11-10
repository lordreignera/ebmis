<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Group;
use App\Models\Branch;
use App\Models\User;

try {
    // Ensure we have a branch and user
    $branch = Branch::first();
    $user = User::first();
    
    if (!$branch) {
        echo "No branches found. Creating default branch...\n";
        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => 1
        ]);
    }
    
    if (!$user) {
        echo "No users found!\n";
        exit(1);
    }
    
    echo "Creating test group...\n";
    $group = Group::create([
        'code' => 'BIMS' . time(),
        'name' => 'Test Legacy Group',
        'inception_date' => '2025-01-01',
        'address' => 'Test Village, Test Parish, Test Subcounty, Test District',
        'sector' => 'Agriculture',
        'type' => 1, // Preliminary (Open)
        'verified' => 0,
        'branch_id' => $branch->id,
        'added_by' => $user->id,
        'datecreated' => now()
    ]);
    
    echo "✅ Group created successfully!\n";
    echo "Group ID: {$group->id}\n";
    echo "Group Code: {$group->code}\n";
    echo "Group Name: {$group->name}\n";
    echo "Sector: {$group->sector}\n";
    echo "Type: " . ($group->type == 1 ? 'Preliminary (Open)' : 'Incubation (Closed)') . "\n";
    echo "Verified: " . ($group->verified ? 'Yes' : 'No') . "\n";
    echo "Branch: {$group->branch->name}\n";
    echo "Date Created: {$group->datecreated}\n";
    
} catch (Exception $e) {
    echo "❌ Error creating group: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}