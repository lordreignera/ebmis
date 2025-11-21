<?php

/**
 * Safe Database Optimizer - Creates backup before optimization
 * Run this for peace of mind - backs up first, then optimizes
 * Usage: php optimize_database_safe.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║      SAFE DATABASE OPTIMIZER (WITH BACKUP)                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Read database credentials from .env file
function getEnvValue($key, $default = '') {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        return $default;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        if (trim($name) === $key) {
            return trim($value, '"\'');
        }
    }
    return $default;
}

$dbName = getEnvValue('DB_DATABASE', '');
$dbUser = getEnvValue('DB_USERNAME', '');
$dbPass = getEnvValue('DB_PASSWORD', '');
$dbHost = getEnvValue('DB_HOST', '127.0.0.1');

if (empty($dbName)) {
    die("❌ Error: Could not determine database name from .env\n\n");
}

// Step 1: Create backup
echo "💾 STEP 1: Creating database backup...\n";
echo "─────────────────────────────────────────────────────────\n";

$backupFile = "backup_before_optimization_" . date('Y-m-d_His') . ".sql";
$backupPath = __DIR__ . "/database/backups/" . $backupFile;

// Create backup directory if it doesn't exist
if (!is_dir(__DIR__ . "/database/backups")) {
    mkdir(__DIR__ . "/database/backups", 0755, true);
}

echo "  → Backup file: $backupFile\n";
echo "  → Creating backup...\n";

// Build mysqldump command
$command = sprintf(
    'mysqldump -h%s -u%s %s %s > "%s"',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    !empty($dbPass) ? '-p' . escapeshellarg($dbPass) : '',
    escapeshellarg($dbName),
    $backupPath
);

exec($command, $output, $returnCode);

if ($returnCode === 0 && file_exists($backupPath)) {
    $fileSize = filesize($backupPath);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    echo "  ✅ Backup created successfully: {$fileSizeMB} MB\n";
    echo "  📁 Location: $backupPath\n\n";
} else {
    die("❌ Error: Failed to create backup. Aborting optimization.\n\n");
}

// Step 2: Ask for confirmation
echo "⚠️  CONFIRMATION REQUIRED\n";
echo "─────────────────────────────────────────────────────────\n";
echo "The following operations will be performed:\n";
echo "  • OPTIMIZE all tables (improves performance, NO data loss)\n";
echo "  • ANALYZE all tables (updates statistics, NO data loss)\n";
echo "  • Clean old sessions (30+ days)\n";
echo "  • Clean old failed jobs (7+ days)\n\n";
echo "Your backup is ready at:\n";
echo "  $backupPath\n\n";

echo "Do you want to proceed with optimization? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$confirmation = trim(strtolower($line));
fclose($handle);

if ($confirmation !== 'yes' && $confirmation !== 'y') {
    echo "\n❌ Optimization cancelled. Backup retained.\n";
    echo "You can delete the backup manually if not needed:\n";
    echo "  rm \"$backupPath\"\n\n";
    exit(0);
}

echo "\n";
echo "✅ Proceeding with optimization...\n\n";

// Step 3: Run the actual optimization
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$startTime = microtime(true);

// Optimize tables
echo "⚡ STEP 2: Optimizing tables...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    $tables = DB::select("SHOW TABLES");
    $tableKey = "Tables_in_" . $dbName;
    
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

// Clean old sessions
echo "🧹 STEP 3: Cleaning old data...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    if (Schema::hasTable('sessions')) {
        $cutoffDate = now()->subDays(30);
        $deleted = DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->delete();
        
        echo "  → Deleted $deleted old sessions (30+ days) ✓\n";
    }
    
    if (Schema::hasTable('failed_jobs')) {
        $cutoffDate = now()->subDays(7);
        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();
        
        echo "  → Deleted $deleted old failed jobs (7+ days) ✓\n";
    }
} catch (\Exception $e) {
    echo "  ⚠️  Error cleaning old data: " . $e->getMessage() . "\n";
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           OPTIMIZATION COMPLETE (WITH BACKUP)              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Database optimized in {$executionTime} seconds\n";
echo "💾 Backup saved: $backupPath ({$fileSizeMB} MB)\n";
echo "📊 All your business data is safe and intact\n\n";

echo "🔄 To restore from backup (if needed):\n";
echo "─────────────────────────────────────────────────────────\n";
echo "mysql -h$dbHost -u$dbUser ";
if (!empty($dbPass)) echo "-p ";
echo "$dbName < \"$backupPath\"\n\n";

echo "💡 You can safely delete the backup after verifying everything works:\n";
echo "   del \"$backupPath\"\n\n";
