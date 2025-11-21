<?php

/**
 * Add Missing Database Indexes for Performance
 * Run this once to add recommended indexes from optimization report
 * Usage: php add_missing_indexes.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         ADD MISSING DATABASE INDEXES                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$startTime = microtime(true);

echo "📊 Database: " . env('DB_DATABASE') . "\n";
echo "🖥️  Host: " . env('DB_HOST') . "\n\n";

// Define indexes to add
$indexesToAdd = [
    'personal_loans' => [
        ['column' => 'member_id', 'name' => 'idx_member_id'],
        ['column' => 'datecreated', 'name' => 'idx_datecreated'],
    ],
    'group_loans' => [
        ['column' => 'branch_id', 'name' => 'idx_branch_id'],
        ['column' => 'datecreated', 'name' => 'idx_datecreated'],
    ],
    'members' => [
        ['column' => 'status', 'name' => 'idx_status'],
    ],
    'fees' => [
        ['column' => 'loan_id', 'name' => 'idx_loan_id'],
        ['column' => 'status', 'name' => 'idx_status'],
        ['column' => 'datecreated', 'name' => 'idx_datecreated'],
    ],
    'loan_schedules' => [
        ['column' => 'status', 'name' => 'idx_status'],
    ],
    'savings' => [
        ['column' => 'status', 'name' => 'idx_status'],
    ],
];

$totalAdded = 0;
$totalSkipped = 0;
$totalErrors = 0;

echo "🔑 Adding missing indexes...\n";
echo "─────────────────────────────────────────────────────────\n\n";

foreach ($indexesToAdd as $table => $indexes) {
    if (!Schema::hasTable($table)) {
        echo "  ⚠️  Table '$table' does not exist - skipping\n\n";
        continue;
    }
    
    echo "  📋 Processing table: $table\n";
    
    foreach ($indexes as $indexDef) {
        $column = $indexDef['column'];
        $indexName = $indexDef['name'];
        
        try {
            // Check if column exists
            if (!Schema::hasColumn($table, $column)) {
                echo "    ⚠️  Column '$column' does not exist - skipping\n";
                $totalSkipped++;
                continue;
            }
            
            // Check if index already exists
            $existingIndexes = DB::select("SHOW INDEX FROM `$table` WHERE Column_name = ?", [$column]);
            
            if (!empty($existingIndexes)) {
                echo "    ✓ Index on '$column' already exists - skipping\n";
                $totalSkipped++;
                continue;
            }
            
            // Add the index
            echo "    → Adding index '$indexName' on '$column'...";
            DB::statement("ALTER TABLE `$table` ADD INDEX `$indexName` (`$column`)");
            echo " ✓\n";
            $totalAdded++;
            
        } catch (\Exception $e) {
            echo " ✗\n";
            echo "      Error: " . $e->getMessage() . "\n";
            $totalErrors++;
        }
    }
    
    echo "\n";
}

// Summary
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    PROCESS COMPLETE                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "📊 SUMMARY:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "  ✅ Indexes added: $totalAdded\n";
echo "  ⏭️  Indexes skipped: $totalSkipped (already exist)\n";
echo "  ❌ Errors: $totalErrors\n";
echo "  ⏱️  Execution time: {$executionTime} seconds\n\n";

if ($totalAdded > 0) {
    echo "✨ SUCCESS! Database indexes have been optimized.\n";
    echo "   Your queries should now run faster!\n\n";
    
    echo "🔄 NEXT STEPS:\n";
    echo "─────────────────────────────────────────────────────────\n";
    echo "1. Run optimizer again: php optimize_database.php\n";
    echo "2. Monitor query performance in production\n";
    echo "3. Check slow query log after deployment\n\n";
} else {
    echo "ℹ️  No new indexes were added.\n";
    echo "   Your database is already optimized!\n\n";
}

echo "💡 TIP: Run 'php optimize_database.php' weekly to keep\n";
echo "   your database performing at its best.\n\n";
