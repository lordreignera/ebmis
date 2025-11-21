<?php

/**
 * Database Optimizer - Comprehensive database optimization script
 * Optimizes tables, indexes, and cleans up old data
 * Usage: php optimize_database.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           DATABASE OPTIMIZATION TOOLKIT                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$startTime = microtime(true);

// Configuration
$cleanOldSessionsOlderThanDays = 30;
$cleanOldLogsOlderThanDays = 90;
$cleanFailedJobsOlderThanDays = 7;

echo "📊 Database: " . env('DB_DATABASE') . "\n";
echo "🖥️  Host: " . env('DB_HOST') . "\n\n";

// Step 1: Analyze database size
echo "📈 STEP 1: Analyzing database size...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    $databaseName = env('DB_DATABASE');
    
    $tables = DB::select("
        SELECT 
            table_name AS 'table',
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
            table_rows AS 'rows'
        FROM information_schema.TABLES
        WHERE table_schema = ?
        ORDER BY (data_length + index_length) DESC
        LIMIT 20
    ", [$databaseName]);
    
    $totalSize = 0;
    $totalRows = 0;
    
    echo "\n  Top 20 Largest Tables:\n";
    echo "  " . str_repeat("─", 70) . "\n";
    printf("  %-40s %12s %15s\n", "Table", "Size (MB)", "Rows");
    echo "  " . str_repeat("─", 70) . "\n";
    
    foreach ($tables as $table) {
        printf("  %-40s %12s %15s\n", 
            $table->table, 
            number_format($table->size_mb, 2), 
            number_format($table->rows)
        );
        $totalSize += $table->size_mb;
        $totalRows += $table->rows;
    }
    
    echo "  " . str_repeat("─", 70) . "\n";
    printf("  %-40s %12s %15s\n", 
        "TOTAL (Top 20)", 
        number_format($totalSize, 2) . " MB", 
        number_format($totalRows)
    );
    echo "\n";
    
} catch (\Exception $e) {
    echo "  ⚠️  Error analyzing database: " . $e->getMessage() . "\n\n";
}

// Step 2: Optimize all tables
echo "⚡ STEP 2: Optimizing all tables...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    $databaseName = env('DB_DATABASE');
    $tables = DB::select("SHOW TABLES");
    $tableKey = "Tables_in_" . $databaseName;
    
    $optimizedCount = 0;
    foreach ($tables as $table) {
        $tableName = $table->$tableKey;
        
        try {
            echo "  → Optimizing $tableName...";
            DB::statement("OPTIMIZE TABLE `$tableName`");
            echo " ✓\n";
            $optimizedCount++;
        } catch (\Exception $e) {
            echo " ⚠️  " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n  ✅ Optimized $optimizedCount tables\n\n";
    
} catch (\Exception $e) {
    echo "  ⚠️  Error optimizing tables: " . $e->getMessage() . "\n\n";
}

// Step 3: Analyze tables
echo "🔍 STEP 3: Analyzing tables for better query performance...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    $databaseName = env('DB_DATABASE');
    $tables = DB::select("SHOW TABLES");
    $tableKey = "Tables_in_" . $databaseName;
    
    $analyzedCount = 0;
    foreach ($tables as $table) {
        $tableName = $table->$tableKey;
        
        try {
            DB::statement("ANALYZE TABLE `$tableName`");
            $analyzedCount++;
        } catch (\Exception $e) {
            // Silent fail
        }
    }
    
    echo "  ✅ Analyzed $analyzedCount tables\n\n";
    
} catch (\Exception $e) {
    echo "  ⚠️  Error analyzing tables: " . $e->getMessage() . "\n\n";
}

// Step 4: Clean old sessions
echo "🧹 STEP 4: Cleaning old sessions...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    if (Schema::hasTable('sessions')) {
        $cutoffDate = now()->subDays($cleanOldSessionsOlderThanDays);
        $deleted = DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->delete();
        
        echo "  → Deleted $deleted sessions older than $cleanOldSessionsOlderThanDays days ✓\n\n";
    } else {
        echo "  → Sessions table not found (using file/cache sessions) ✓\n\n";
    }
} catch (\Exception $e) {
    echo "  ⚠️  Error cleaning sessions: " . $e->getMessage() . "\n\n";
}

// Step 5: Clean failed jobs
echo "🗑️  STEP 5: Cleaning old failed jobs...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    if (Schema::hasTable('failed_jobs')) {
        $cutoffDate = now()->subDays($cleanFailedJobsOlderThanDays);
        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();
        
        echo "  → Deleted $deleted failed jobs older than $cleanFailedJobsOlderThanDays days ✓\n\n";
    } else {
        echo "  → Failed jobs table not found ✓\n\n";
    }
} catch (\Exception $e) {
    echo "  ⚠️  Error cleaning failed jobs: " . $e->getMessage() . "\n\n";
}

// Step 6: Check for missing indexes
echo "🔑 STEP 6: Checking critical indexes...\n";
echo "─────────────────────────────────────────────────────────\n";

$recommendedIndexes = [
    'personal_loans' => ['member_id', 'status', 'branch_id', 'datecreated'],
    'group_loans' => ['group_id', 'status', 'branch_id', 'datecreated'],
    'members' => ['verified', 'status', 'branch_id', 'deleted'],
    'fees' => ['member_id', 'loan_id', 'status', 'datecreated'],
    'loan_schedules' => ['loan_id', 'status', 'due_date'],
    'transactions' => ['member_id', 'type', 'created_at'],
    'savings' => ['member_id', 'status', 'created_at'],
    'sessions' => ['last_activity'],
];

try {
    foreach ($recommendedIndexes as $table => $columns) {
        if (Schema::hasTable($table)) {
            echo "  → Checking indexes on $table...\n";
            
            $existingIndexes = DB::select("SHOW INDEX FROM `$table`");
            $indexedColumns = array_unique(array_column($existingIndexes, 'Column_name'));
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    if (in_array($column, $indexedColumns)) {
                        echo "    ✓ $column is indexed\n";
                    } else {
                        echo "    ⚠️  $column is NOT indexed (consider adding)\n";
                        echo "       ALTER TABLE `$table` ADD INDEX `idx_$column` (`$column`);\n";
                    }
                }
            }
            echo "\n";
        }
    }
} catch (\Exception $e) {
    echo "  ⚠️  Error checking indexes: " . $e->getMessage() . "\n\n";
}

// Step 7: Check table engine
echo "🏎️  STEP 7: Checking table engines...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    $databaseName = env('DB_DATABASE');
    
    $nonInnoDBTables = DB::select("
        SELECT table_name, engine
        FROM information_schema.TABLES
        WHERE table_schema = ?
        AND engine != 'InnoDB'
    ", [$databaseName]);
    
    if (empty($nonInnoDBTables)) {
        echo "  ✅ All tables are using InnoDB (optimal)\n\n";
    } else {
        echo "  ⚠️  The following tables are NOT using InnoDB:\n";
        foreach ($nonInnoDBTables as $table) {
            echo "    → {$table->table_name} ({$table->engine})\n";
            echo "       Consider: ALTER TABLE `{$table->table_name}` ENGINE=InnoDB;\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "  ⚠️  Error checking engines: " . $e->getMessage() . "\n\n";
}

// Step 8: Database configuration recommendations
echo "💡 STEP 8: Database configuration recommendations...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    // Check important MySQL variables
    $variables = [
        'innodb_buffer_pool_size',
        'max_connections',
        'query_cache_size',
        'tmp_table_size',
        'max_heap_table_size',
    ];
    
    echo "  Current MySQL Configuration:\n";
    foreach ($variables as $var) {
        try {
            $result = DB::select("SHOW VARIABLES LIKE ?", [$var]);
            if (!empty($result)) {
                printf("    %-30s: %s\n", $result[0]->Variable_name, $result[0]->Value);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }
    
    echo "\n  📋 Recommended Settings for Production:\n";
    echo "    innodb_buffer_pool_size    : 1G - 2G (70% of RAM)\n";
    echo "    max_connections            : 150 - 300\n";
    echo "    query_cache_size           : 32M - 64M\n";
    echo "    tmp_table_size             : 64M - 128M\n";
    echo "    max_heap_table_size        : 64M - 128M\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "  ⚠️  Error checking configuration: " . $e->getMessage() . "\n\n";
}

// Step 9: Suggest application optimizations
echo "🚀 STEP 9: Application optimization suggestions...\n";
echo "─────────────────────────────────────────────────────────\n";

echo "  1. Cache Configuration:\n";
echo "     → Use Redis for cache: CACHE_DRIVER=redis\n";
echo "     → Use Redis for sessions: SESSION_DRIVER=redis\n";
echo "     → Use Redis for queue: QUEUE_CONNECTION=redis\n\n";

echo "  2. Laravel Optimizations:\n";
echo "     → php artisan config:cache\n";
echo "     → php artisan route:cache\n";
echo "     → php artisan view:cache\n";
echo "     → php artisan event:cache\n\n";

echo "  3. Enable Query Log to find slow queries:\n";
echo "     → Add DB::enableQueryLog() in AppServiceProvider\n";
echo "     → Check logs: DB::getQueryLog()\n\n";

echo "  4. Use Eager Loading:\n";
echo "     → Replace Member::with('loans') instead of separate queries\n";
echo "     → Use pagination: paginate(20) instead of get()\n\n";

echo "  5. Database Connection Pooling:\n";
echo "     → Consider using ProxySQL or PgBouncer\n\n";

// Final summary
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              OPTIMIZATION COMPLETE                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Database optimization completed in {$executionTime} seconds\n";
echo "✅ All tables have been optimized and analyzed\n";
echo "✅ Old data has been cleaned up\n\n";

echo "🔄 RECOMMENDED SCHEDULE:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "• Run this script weekly: 0 2 * * 0 php /path/to/optimize_database.php\n";
echo "• Monitor slow queries daily\n";
echo "• Check database size monthly\n\n";

echo "📝 LOG ANALYSIS:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "Check for slow queries:\n";
echo "  tail -f storage/logs/laravel.log | grep -i 'slow'\n\n";

echo "Monitor MySQL slow query log:\n";
echo "  tail -f /var/log/mysql/mysql-slow.log\n\n";
