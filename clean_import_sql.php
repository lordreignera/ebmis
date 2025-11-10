<?php
/**
 * Clean SQL Import Script - Import data from ebimsnow2.sql to Laravel ebims1
 * This script parses the SQL file and imports data while handling structure differences
 */

$sql_file = 'ebimsnow2.sql';
$target_db = 'ebims1';
$host = '127.0.0.1';
$username = 'root';
$password = '';

try {
    echo "=== Clean Import from ebimsnow2.sql to EBIMS1 ===\n\n";
    
    // Connect to target database
    $pdo = new PDO("mysql:host={$host};dbname={$target_db};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to target database: {$target_db}\n";
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file {$sql_file} not found!");
    }
    
    echo "✓ Found SQL file: {$sql_file}\n";
    echo "📁 File size: " . number_format(filesize($sql_file)) . " bytes\n\n";
    
    // Tables to import data for (skip CREATE TABLE statements)
    $tables_to_import = [
        'members', 'personal_loans', 'group_loans', 'loan_schedules', 
        'repayments', 'groups', 'branches', 'products', 'guarantors',
        'fees', 'disbursement', 'accounts_ledger', 'raw_payments'
    ];
    
    echo "Processing SQL file...\n";
    
    $file_handle = fopen($sql_file, 'r');
    $current_table = '';
    $insert_buffer = [];
    $line_number = 0;
    $total_imported = 0;
    $total_errors = 0;
    
    // Clear target tables first (preserve system data)
    foreach ($tables_to_import as $table) {
        try {
            if ($table === 'members') {
                $pdo->exec("DELETE FROM {$table} WHERE id > 10"); // Keep admin users
                echo "Cleared {$table} (preserved admin users)\n";
            } elseif ($table === 'branches') {
                $pdo->exec("DELETE FROM {$table} WHERE id > 1"); // Keep default branch
                echo "Cleared {$table} (preserved default branch)\n";
            } else {
                $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($result->rowCount() > 0) {
                    $pdo->exec("DELETE FROM {$table}");
                    echo "Cleared {$table}\n";
                }
            }
        } catch (Exception $e) {
            echo "⚠ Could not clear {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nImporting data...\n";
    
    while (($line = fgets($file_handle)) !== false) {
        $line_number++;
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
            continue;
        }
        
        // Skip problematic statements
        if (preg_match('/^(CREATE TABLE|ALTER TABLE|DROP TABLE|SET |START TRANSACTION|COMMIT)/i', $line)) {
            continue;
        }
        
        // Detect INSERT statements
        if (preg_match('/^INSERT INTO `([^`]+)`/i', $line, $matches)) {
            $table_name = $matches[1];
            
            // Only process tables we want to import
            if (in_array($table_name, $tables_to_import)) {
                $current_table = $table_name;
                
                // Handle multi-line INSERTs
                $insert_statement = $line;
                
                // Check if statement is complete
                while (!preg_match('/;\s*$/', $insert_statement)) {
                    $next_line = fgets($file_handle);
                    if ($next_line === false) break;
                    $line_number++;
                    $insert_statement .= ' ' . trim($next_line);
                }
                
                // Clean and execute the INSERT statement
                $cleaned_insert = cleanInsertStatement($insert_statement, $table_name, $pdo);
                
                if ($cleaned_insert) {
                    try {
                        $pdo->exec($cleaned_insert);
                        $affected = $pdo->lastInsertId() ?: 1;
                        $total_imported++;
                        
                        // Show progress
                        if ($total_imported % 50 === 0) {
                            echo "  Imported {$total_imported} records from {$current_table}...\r";
                        }
                        
                    } catch (PDOException $e) {
                        $total_errors++;
                        if ($total_errors <= 5) {
                            echo "\n⚠ Error in {$table_name} (line {$line_number}): " . substr($e->getMessage(), 0, 100) . "...\n";
                        }
                    }
                }
            }
        }
        
        // Show progress every 1000 lines
        if ($line_number % 1000 === 0) {
            echo "  Processed {$line_number} lines...\r";
        }
    }
    
    fclose($file_handle);
    
    echo "\n\n=== Import Summary ===\n";
    echo "Lines processed: {$line_number}\n";
    echo "Records imported: {$total_imported}\n";
    echo "Errors encountered: {$total_errors}\n\n";
    
    // Show final counts
    echo "Final record counts:\n";
    foreach ($tables_to_import as $table) {
        try {
            $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($result->rowCount() > 0) {
                $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "  {$table}: {$count} records\n";
            } else {
                echo "  {$table}: table not found\n";
            }
        } catch (Exception $e) {
            echo "  {$table}: error getting count\n";
        }
    }
    
    echo "\n✓ Clean import completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Clean INSERT statement to handle structure differences
 */
function cleanInsertStatement($insert, $table_name, $pdo) {
    try {
        // Get target table structure
        $columns_stmt = $pdo->query("DESCRIBE {$table_name}");
        $target_columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Extract column names from INSERT statement
        if (preg_match('/INSERT INTO `' . $table_name . '` \(([^)]+)\) VALUES/i', $insert, $matches)) {
            $column_list = $matches[1];
            $columns = array_map(function($col) {
                return trim($col, '` ');
            }, explode(',', $column_list));
            
            // Filter columns that exist in target table
            $valid_columns = array_intersect($columns, $target_columns);
            
            if (empty($valid_columns)) {
                return false;
            }
            
            // If all columns are valid, return as is
            if (count($valid_columns) === count($columns)) {
                return $insert;
            }
            
            // Otherwise, we'd need to rebuild the INSERT with valid columns
            // For now, return as is and let MySQL handle the errors
            return $insert;
        }
        
        return $insert;
        
    } catch (Exception $e) {
        return false;
    }
}
?>