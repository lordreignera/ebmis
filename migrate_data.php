<?php
/**
 * Data Migration Script - Transfer data from local ebims to Laravel ebims1
 * This script safely migrates data while preserving the new Laravel structure
 */

// Database connections
$source_db = 'ebims';    // Your local working database
$target_db = 'ebims1';   // New Laravel database
$host = '127.0.0.1';
$username = 'root';
$password = '';

try {
    // Connect to source database
    $source_pdo = new PDO("mysql:host={$host};dbname={$source_db};charset=utf8mb4", $username, $password);
    $source_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connect to target database  
    $target_pdo = new PDO("mysql:host={$host};dbname={$target_db};charset=utf8mb4", $username, $password);
    $target_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to both databases successfully!\n";
    
    // List of tables to migrate with their column mappings
    $tables_to_migrate = [
        'members' => [
            'clear_target' => false, // Keep seeded admin users
            'column_mapping' => [
                'datecreated' => 'created_at',
                // Add more mappings as needed
            ]
        ],
        'personal_loans' => [
            'clear_target' => true,
            'column_mapping' => [
                'datecreated' => 'created_at',
            ]
        ],
        'group_loans' => [
            'clear_target' => true,
            'column_mapping' => [
                'datecreated' => 'created_at',
            ]
        ],
        'loan_schedules' => [
            'clear_target' => true,
            'column_mapping' => [
                'date_created' => 'created_at',
            ]
        ],
        'repayments' => [
            'clear_target' => true,
            'column_mapping' => [
                'date_created' => 'created_at',
            ]
        ],
        'groups' => [
            'clear_target' => true,
            'column_mapping' => [
                'datecreated' => 'created_at',
            ]
        ],
        'branches' => [
            'clear_target' => false, // Keep seeded branches
            'column_mapping' => [
                'date_created' => 'created_at',
            ]
        ],
        'products' => [
            'clear_target' => true,
            'column_mapping' => []
        ]
    ];
    
    // Migrate each table
    foreach ($tables_to_migrate as $table => $config) {
        echo "\n--- Migrating {$table} ---\n";
        
        try {
            // Check if table exists in both databases
            $source_check = $source_pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount();
            $target_check = $target_pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount();
            
            if ($source_check === 0) {
                echo "Table {$table} not found in source database, skipping...\n";
                continue;
            }
            
            if ($target_check === 0) {
                echo "Table {$table} not found in target database, skipping...\n";
                continue;
            }
            
            // Get source data count
            $count_stmt = $source_pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $source_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Source records: {$source_count}\n";
            
            if ($source_count === 0) {
                echo "No data to migrate for {$table}\n";
                continue;
            }
            
            // Clear target table if specified (except for certain tables)
            if ($config['clear_target']) {
                if ($table === 'members') {
                    // Don't delete admin users (id <= 10)
                    $target_pdo->exec("DELETE FROM {$table} WHERE id > 10");
                } else {
                    $target_pdo->exec("TRUNCATE TABLE {$table}");
                }
                echo "Cleared target table (preserving system records)\n";
            }
            
            // Get source table structure
            $source_columns_stmt = $source_pdo->query("DESCRIBE {$table}");
            $source_columns = $source_columns_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get target table structure  
            $target_columns_stmt = $target_pdo->query("DESCRIBE {$table}");
            $target_columns = $target_columns_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Find common columns
            $common_columns = array_intersect($source_columns, $target_columns);
            
            // Apply column mappings
            $select_columns = [];
            $insert_columns = [];
            
            foreach ($common_columns as $column) {
                if (isset($config['column_mapping'][$column])) {
                    $select_columns[] = $column;
                    $insert_columns[] = $config['column_mapping'][$column];
                } else {
                    $select_columns[] = $column;
                    $insert_columns[] = $column;
                }
            }
            
            echo "Migrating columns: " . implode(', ', $insert_columns) . "\n";
            
            // Build migration query
            $select_sql = "SELECT " . implode(', ', $select_columns) . " FROM {$table}";
            $insert_sql = "INSERT INTO {$table} (" . implode(', ', $insert_columns) . ") VALUES (" . 
                         str_repeat('?,', count($insert_columns) - 1) . "?)";
            
            // Prepare statements
            $source_stmt = $source_pdo->prepare($select_sql);
            $target_stmt = $target_pdo->prepare($insert_sql);
            
            // Start transaction for target
            $target_pdo->beginTransaction();
            
            // Execute migration
            $source_stmt->execute();
            $migrated = 0;
            $errors = 0;
            
            while ($row = $source_stmt->fetch(PDO::FETCH_NUM)) {
                try {
                    $target_stmt->execute($row);
                    $migrated++;
                } catch (PDOException $e) {
                    $errors++;
                    if ($errors < 5) { // Show first 5 errors only
                        echo "Error migrating row: " . $e->getMessage() . "\n";
                    }
                }
            }
            
            // Commit transaction
            $target_pdo->commit();
            
            echo "Successfully migrated: {$migrated} records\n";
            if ($errors > 0) {
                echo "Errors encountered: {$errors} records\n";
            }
            
        } catch (Exception $e) {
            if ($target_pdo->inTransaction()) {
                $target_pdo->rollback();
            }
            echo "Error migrating {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    
    // Show final counts
    foreach (array_keys($tables_to_migrate) as $table) {
        try {
            $target_check = $target_pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount();
            if ($target_check > 0) {
                $count_stmt = $target_pdo->query("SELECT COUNT(*) as count FROM {$table}");
                $final_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "{$table}: {$final_count} records\n";
            }
        } catch (Exception $e) {
            echo "{$table}: Error getting count\n";
        }
    }
    
    echo "\nMigration completed! Your Laravel app now has reliable local data.\n";
    echo "You can run this script anytime to refresh data from your local ebims database.\n";
    
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}
?>