<?php
/**
 * Standalone Script to Convert Tables to InnoDB
 * 
 * This script converts MyISAM tables to InnoDB to support foreign key constraints.
 * Run this BEFORE running school loan migrations on the online server.
 * 
 * Usage:
 *   php convert_tables_to_innodb.php
 * 
 * Or access via browser:
 *   http://yourdomain.com/convert_tables_to_innodb.php
 */

// Load Laravel bootstrap if run from Laravel directory
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $pdo = DB::connection()->getPdo();
    $dbname = DB::connection()->getDatabaseName();
} else {
    // Manual database connection (update these values)
    $host = 'localhost';
    $dbname = 'ebims1';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✓ Database connection established\n";
    } catch (PDOException $e) {
        die("✗ Connection failed: " . $e->getMessage() . "\n");
    }
}

// Tables that need to be converted
$tables = [
    'schools',
    'students',
    'staff',
    'users',
    'products',
    'branches'
];

echo "\n========================================\n";
echo "Converting Tables to InnoDB\n";
echo "========================================\n\n";

$success = 0;
$failed = 0;
$skipped = 0;

foreach ($tables as $table) {
    echo "Processing table: {$table}...\n";
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() == 0) {
            echo "  ⚠ Table does not exist, skipping\n\n";
            $skipped++;
            continue;
        }
        
        // Get current engine
        $stmt = $pdo->query("SELECT ENGINE FROM information_schema.TABLES 
                            WHERE TABLE_SCHEMA = '{$dbname}' 
                            AND TABLE_NAME = '{$table}'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo "  ⚠ Could not determine table engine, skipping\n\n";
            $skipped++;
            continue;
        }
        
        $currentEngine = $result['ENGINE'];
        echo "  Current engine: {$currentEngine}\n";
        
        if ($currentEngine === 'InnoDB') {
            echo "  ✓ Already InnoDB, no conversion needed\n\n";
            $success++;
            continue;
        }
        
        // Convert to InnoDB
        echo "  Converting from {$currentEngine} to InnoDB...\n";
        $pdo->exec("ALTER TABLE `{$table}` ENGINE=InnoDB");
        echo "  ✓ Successfully converted to InnoDB\n";
        
        // Convert charset to utf8mb4
        echo "  Converting charset to utf8mb4...\n";
        $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  ✓ Successfully converted charset\n\n";
        
        $success++;
        
    } catch (PDOException $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n\n";
        $failed++;
    }
}

echo "========================================\n";
echo "Conversion Summary\n";
echo "========================================\n";
echo "✓ Successful: {$success}\n";
echo "✗ Failed: {$failed}\n";
echo "⚠ Skipped: {$skipped}\n";
echo "Total: " . count($tables) . "\n\n";

if ($failed > 0) {
    echo "⚠ Some tables failed to convert. Please check errors above.\n";
    echo "You may need to:\n";
    echo "  1. Check table permissions\n";
    echo "  2. Ensure sufficient disk space\n";
    echo "  3. Check for corrupted tables (run REPAIR TABLE)\n\n";
} else if ($success == count($tables)) {
    echo "✅ All tables successfully converted!\n";
    echo "You can now run: php artisan migrate\n\n";
}

// Verify conversions
echo "========================================\n";
echo "Verification\n";
echo "========================================\n";
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT ENGINE FROM information_schema.TABLES 
                            WHERE TABLE_SCHEMA = '{$dbname}' 
                            AND TABLE_NAME = '{$table}'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $icon = $result['ENGINE'] === 'InnoDB' ? '✓' : '✗';
            echo "{$icon} {$table}: {$result['ENGINE']}\n";
        }
    } catch (PDOException $e) {
        echo "✗ {$table}: Error checking\n";
    }
}

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n\n";
