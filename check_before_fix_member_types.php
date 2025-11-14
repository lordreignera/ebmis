<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Foreign Key Constraints ===\n\n";

// Check if member_types has foreign keys
$fks = DB::select("
    SELECT 
        CONSTRAINT_NAME, 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
        REFERENCED_TABLE_NAME = 'member_types'
        AND TABLE_SCHEMA = DATABASE()
");

if (empty($fks)) {
    echo "✓ No foreign key constraints reference member_types table\n";
    echo "✓ Safe to update IDs directly\n";
} else {
    echo "Foreign keys found:\n";
    foreach ($fks as $fk) {
        echo "  - {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    }
    echo "\n⚠ WARNING: Need to temporarily disable foreign key checks\n";
}

echo "\n=== Checking Current State ===\n\n";

// Count members by type
$counts = DB::table('members')
    ->select('member_type', DB::raw('COUNT(*) as count'))
    ->groupBy('member_type')
    ->orderBy('member_type')
    ->get();

echo "Current members distribution:\n";
foreach ($counts as $count) {
    $type = DB::table('member_types')->where('id', $count->member_type)->value('name');
    echo "  member_type {$count->member_type} ('{$type}'): {$count->count} members\n";
}

echo "\nWith current mapping (WRONG):\n";
$type1 = $counts->firstWhere('member_type', 1);
$type2 = $counts->firstWhere('member_type', 2);
echo "  1 (Individual) → Actually displays as 'Group' → " . ($type1 ? $type1->count : 0) . " members\n";
echo "  2 (Group) → Actually displays as 'Individual' → " . ($type2 ? $type2->count : 0) . " members\n";

echo "\nAfter fix (CORRECT):\n";
echo "  1 (Group) → Will display as 'Group' → " . ($type1 ? $type1->count : 0) . " members\n";
echo "  2 (Individual) → Will display as 'Individual' → " . ($type2 ? $type2->count : 0) . " members\n";

echo "\n=== Ready to Fix ===\n";
