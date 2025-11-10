<?php

/**
 * Pre-Deployment Database Check Script
 * Run this script on production to verify database compatibility before deploying
 * 
 * Usage: php artisan tinker --execute="require_once('database/verify_production_compatibility.php');"
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== EBIMS Production Database Compatibility Check ===\n\n";

// 1. Check if members table exists
if (!Schema::hasTable('members')) {
    echo "❌ CRITICAL: members table does not exist!\n";
    exit(1);
}

echo "✅ Members table exists\n";

// 2. Check required columns
$requiredColumns = [
    'id', 'fname', 'lname', 'contact', 'email', 'group_id', 'branch_id'
];

$missingColumns = [];
foreach ($requiredColumns as $column) {
    if (!Schema::hasColumn('members', $column)) {
        $missingColumns[] = $column;
    }
}

if (!empty($missingColumns)) {
    echo "❌ CRITICAL: Missing required columns in members table: " . implode(', ', $missingColumns) . "\n";
    exit(1);
}

echo "✅ All required columns exist\n";

// 3. Check if mobile_pin column exists
$hasMobilePin = Schema::hasColumn('members', 'mobile_pin');
if ($hasMobilePin) {
    echo "✅ mobile_pin column already exists\n";
} else {
    echo "⚠️  mobile_pin column missing - will be added by migration\n";
}

// 4. Check foreign key dependencies
$dependencyTables = ['countries', 'branches', 'groups', 'member_types', 'users'];
$missingTables = [];

foreach ($dependencyTables as $table) {
    if (!Schema::hasTable($table)) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "❌ CRITICAL: Missing dependency tables: " . implode(', ', $missingTables) . "\n";
    exit(1);
}

echo "✅ All dependency tables exist\n";

// 5. Check member data integrity
$memberCount = DB::table('members')->count();
echo "📊 Total members in database: {$memberCount}\n";

if ($memberCount > 0) {
    // Check for null required fields
    $nullChecks = [
        'fname' => DB::table('members')->whereNull('fname')->count(),
        'lname' => DB::table('members')->whereNull('lname')->count(),
        'contact' => DB::table('members')->whereNull('contact')->count(),
    ];
    
    foreach ($nullChecks as $field => $nullCount) {
        if ($nullCount > 0) {
            echo "⚠️  Warning: {$nullCount} members have null {$field}\n";
        }
    }
}

// 6. Check migrations table
$lastMigration = DB::table('migrations')->orderBy('id', 'desc')->first();
if ($lastMigration) {
    echo "📋 Last migration run: {$lastMigration->migration}\n";
    echo "🕐 Last migration batch: {$lastMigration->batch}\n";
} else {
    echo "⚠️  No migrations found in migrations table\n";
}

echo "\n=== DEPLOYMENT RECOMMENDATIONS ===\n";

if (!$hasMobilePin) {
    echo "1. Run: php artisan migrate (to add mobile_pin column)\n";
}

echo "2. Run: php artisan optimize:clear (to clear all caches)\n";
echo "3. Backup database before deployment\n";
echo "4. Test member registration form after deployment\n";

echo "\n✅ Database compatibility check completed successfully!\n";