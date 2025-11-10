<?php
/**
 * Data Migration Script - Transfer data from ebmisnow to Laravel ebims1
 * This script safely migrates data from the online database while handling structure differences
 */

// Database connections
$source_db = 'ebmisnow';  // Your online database
$target_db = 'ebims1';    // New Laravel database
$host = '127.0.0.1';
$username = 'root';
$password = '';

try {
    echo "=== EBMISNOW to EBIMS1 Data Migration ===\n\n";
    
    // Connect to source database (ebmisnow)
    $source_pdo = new PDO("mysql:host={$host};dbname={$source_db};charset=utf8mb4", $username, $password);
    $source_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to source database: {$source_db}\n";
    
    // Connect to target database (ebims1)
    $target_pdo = new PDO("mysql:host={$host};dbname={$target_db};charset=utf8mb4", $username, $password);
    $target_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to target database: {$target_db}\n\n";
    
    // List of tables to migrate with their configurations
    $tables_to_migrate = [
        'members' => [
            'clear_target' => false, // Keep seeded admin users
            'where_clause' => '', // Migrate all members
            'column_mapping' => [
                'datecreated' => 'created_at',
                'datemodified' => 'updated_at'
            ],
            'exclude_columns' => ['password_hash'], // Skip problematic columns
            'default_values' => [
                'updated_at' => 'NOW()',
                'email_verified_at' => 'NULL'
            ]
        ],
        'personal_loans' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'datecreated' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()',
                'otp_code' => 'NULL',
                'otp_expires_at' => 'NULL', 
                'signature_status' => "'pending'",
                'signed_at' => 'NULL',
                'signature_ip' => 'NULL'
            ]
        ],
        'group_loans' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'datecreated' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()',
                'otp_code' => 'NULL',
                'otp_expires_at' => 'NULL',
                'signature_status' => "'pending'", 
                'signed_at' => 'NULL',
                'signature_ip' => 'NULL'
            ]
        ],
        'loan_schedules' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'date_created' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()'
            ]
        ],
        'repayments' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'date_created' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()'
            ]
        ],
        'groups' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'datecreated' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()'
            ]
        ],
        'branches' => [
            'clear_target' => false, // Keep seeded data
            'where_clause' => 'WHERE id > 1', // Skip default branch
            'column_mapping' => [
                'date_created' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()',
                'manager_id' => 'NULL',
                'is_active' => '1'
            ]
        ],
        'products' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [],
            'exclude_columns' => [],
            'default_values' => [
                'created_at' => 'NOW()',
                'updated_at' => 'NOW()'
            ]
        ],
        'guarantors' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'datecreated' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()'
            ]
        ],
        'disbursement' => [
            'clear_target' => true,
            'where_clause' => '',
            'column_mapping' => [
                'datecreated' => 'created_at'
            ],
            'exclude_columns' => [],
            'default_values' => [
                'updated_at' => 'NOW()'
            ],
            'target_table' => 'disbursements' // Different table name in Laravel
        ]
    ];
    
    $total_migrated = 0;
    $total_errors = 0;
    
    // Migrate each table
    foreach ($tables_to_migrate as $source_table => $config) {
        $target_table = $config['target_table'] ?? $source_table;
        echo "--- Migrating {$source_table} -> {$target_table} ---\n";
        
        try {
            // Check if tables exist
            $source_check = $source_pdo->query("SHOW TABLES LIKE '{$source_table}'")->rowCount();
            $target_check = $target_pdo->query("SHOW TABLES LIKE '{$target_table}'")->rowCount();
            
            if ($source_check === 0) {
                echo "⚠ Table {$source_table} not found in source database\n\n";
                continue;
            }
            
            if ($target_check === 0) {
                echo "⚠ Table {$target_table} not found in target database\n\n";
                continue;
            }
            
            // Get source data count
            $where = $config['where_clause'];
            $count_sql = "SELECT COUNT(*) as count FROM {$source_table} {$where}";
            $count_stmt = $source_pdo->query($count_sql);
            $source_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Source records: {$source_count}\n";
            
            if ($source_count === 0) {
                echo "No data to migrate\n\n";
                continue;
            }
            
            // Clear target table if specified
            if ($config['clear_target']) {
                if ($source_table === 'members') {
                    // Keep admin users (typically id <= 10)
                    $target_pdo->exec("DELETE FROM {$target_table} WHERE id > 10");
                    echo "Cleared target table (preserved admin users)\n";
                } else {
                    $target_pdo->exec("DELETE FROM {$target_table}");
                    echo "Cleared target table\n";
                }
            }
            
            // Get table structures
            $source_columns_stmt = $source_pdo->query("DESCRIBE {$source_table}");
            $source_columns = $source_columns_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $target_columns_stmt = $target_pdo->query("DESCRIBE {$target_table}");
            $target_columns = $target_columns_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Build column lists
            $source_select = [];
            $target_insert = [];
            $value_placeholders = [];
            
            // Add common columns (excluding problematic ones)
            foreach ($source_columns as $col) {
                if (in_array($col, $config['exclude_columns'])) {
                    continue;
                }
                
                $target_col = $config['column_mapping'][$col] ?? $col;
                
                if (in_array($target_col, $target_columns)) {
                    $source_select[] = $col;
                    $target_insert[] = $target_col;
                    $value_placeholders[] = '?';
                }
            }
            
            // Add default values for new Laravel columns
            foreach ($config['default_values'] as $col => $value) {
                if (in_array($col, $target_columns) && !in_array($col, $target_insert)) {
                    $target_insert[] = $col;
                    $value_placeholders[] = $value;
                }
            }
            
            echo "Migrating columns: " . implode(', ', $target_insert) . "\n";
            
            // Build SQL statements
            $select_sql = "SELECT " . implode(', ', $source_select) . " FROM {$source_table} {$where}";
            $insert_sql = "INSERT IGNORE INTO {$target_table} (" . implode(', ', $target_insert) . 
                         ") VALUES (" . implode(', ', $value_placeholders) . ")";
            
            // Prepare statements
            $source_stmt = $source_pdo->prepare($select_sql);
            $target_stmt = $target_pdo->prepare($insert_sql);
            
            // Execute migration
            $source_stmt->execute();
            $migrated = 0;
            $errors = 0;
            
            echo "Migrating data...\n";
            
            while ($row = $source_stmt->fetch(PDO::FETCH_NUM)) {
                try {
                    $target_stmt->execute($row);
                    $migrated++;
                    
                    // Show progress every 100 records
                    if ($migrated % 100 === 0) {
                        echo "  Migrated: {$migrated} records\r";
                    }
                } catch (PDOException $e) {
                    $errors++;
                    if ($errors <= 3) { // Show first 3 errors only
                        echo "\n⚠ Error: " . substr($e->getMessage(), 0, 100) . "...\n";
                    }
                }
            }
            
            echo "\n✓ Successfully migrated: {$migrated} records\n";
            if ($errors > 0) {
                echo "⚠ Errors encountered: {$errors} records\n";
            }
            
            $total_migrated += $migrated;
            $total_errors += $errors;
            
        } catch (Exception $e) {
            echo "❌ Error migrating {$source_table}: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== Migration Summary ===\n";
    echo "Total records migrated: {$total_migrated}\n";
    echo "Total errors: {$total_errors}\n\n";
    
    // Show final record counts
    echo "Final record counts in ebims1:\n";
    foreach (array_keys($tables_to_migrate) as $table) {
        $target_table = $tables_to_migrate[$table]['target_table'] ?? $table;
        try {
            if ($target_pdo->query("SHOW TABLES LIKE '{$target_table}'")->rowCount() > 0) {
                $count_stmt = $target_pdo->query("SELECT COUNT(*) as count FROM {$target_table}");
                $final_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "  {$target_table}: {$final_count} records\n";
            }
        } catch (Exception $e) {
            echo "  {$target_table}: Error getting count\n";
        }
    }
    
    echo "\n✓ Migration from ebmisnow to ebims1 completed!\n";
    echo "Your Laravel application now has the online database data.\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
    echo "Make sure both ebmisnow and ebims1 databases exist and are accessible.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>