<?php
// Quick Member Import Fix
$host = '127.0.0.1';
$username = 'root';
$password = '';
$target_db = 'ebims1';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$target_db};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Quick member import fix...\n";
    
    // Clear existing members (keep admin)
    $pdo->exec("DELETE FROM members WHERE id > 10");
    echo "Cleared existing members\n";
    
    // Read SQL file and extract member inserts
    $sql_content = file_get_contents('ebimsnow2.sql');
    
    // Find all member INSERT statements
    preg_match_all('/INSERT INTO `members`[^;]+;/s', $sql_content, $matches);
    
    echo "Found " . count($matches[0]) . " member INSERT statements\n";
    
    $imported = 0;
    $errors = 0;
    
    foreach ($matches[0] as $insert) {
        try {
            // Remove problematic columns that don't exist in Laravel
            $cleaned = preg_replace('/`email_verified_at`[^,)]*,?/', '', $insert);
            $cleaned = preg_replace('/`password_hash`[^,)]*,?/', '', $cleaned);
            
            $pdo->exec($cleaned);
            $imported++;
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 3) {
                echo "Error: " . substr($e->getMessage(), 0, 80) . "...\n";
            }
        }
    }
    
    echo "Imported: {$imported} members\n";
    echo "Errors: {$errors}\n";
    
    // Final count
    $result = $pdo->query("SELECT COUNT(*) as count FROM members");
    $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total members now: {$count}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>