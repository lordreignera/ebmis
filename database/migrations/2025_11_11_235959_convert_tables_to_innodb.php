<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration converts existing tables from MyISAM to InnoDB
     * to support foreign key constraints for school loan tables.
     */
    public function up(): void
    {
        // List of tables that need to be converted to InnoDB
        $tables = [
            'schools',
            'students', 
            'staff',
            'users',
            'products',
            'branches'
        ];

        foreach ($tables as $table) {
            // Check if table exists
            if (Schema::hasTable($table)) {
                try {
                    // Get current engine
                    $engine = DB::select("SELECT ENGINE 
                                         FROM information_schema.TABLES 
                                         WHERE TABLE_SCHEMA = DATABASE() 
                                         AND TABLE_NAME = ?", [$table]);
                    
                    if (!empty($engine) && $engine[0]->ENGINE !== 'InnoDB') {
                        echo "Converting table '{$table}' from {$engine[0]->ENGINE} to InnoDB...\n";
                        
                        // Convert table engine to InnoDB
                        DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
                        
                        echo "✓ Successfully converted '{$table}' to InnoDB\n";
                    } else {
                        echo "✓ Table '{$table}' is already InnoDB\n";
                    }
                } catch (\Exception $e) {
                    echo "⚠ Warning: Could not convert '{$table}': " . $e->getMessage() . "\n";
                    // Continue with other tables even if one fails
                }
            } else {
                echo "⚠ Table '{$table}' does not exist, skipping...\n";
            }
        }

        // Also ensure utf8mb4 charset for compatibility
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    $charset = DB::select("SELECT CCSA.character_set_name 
                                          FROM information_schema.TABLES T,
                                               information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA
                                          WHERE CCSA.collation_name = T.table_collation
                                            AND T.table_schema = DATABASE()
                                            AND T.table_name = ?", [$table]);
                    
                    if (!empty($charset) && !in_array($charset[0]->character_set_name, ['utf8mb4'])) {
                        echo "Converting table '{$table}' charset to utf8mb4...\n";
                        
                        DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        echo "✓ Successfully converted '{$table}' charset to utf8mb4\n";
                    }
                } catch (\Exception $e) {
                    echo "⚠ Warning: Could not convert '{$table}' charset: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "\n✅ All table conversions completed!\n\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't reverse this migration as converting back to MyISAM
        // could break foreign key constraints in dependent tables
        echo "⚠ Note: Table engine conversion is not reversed to maintain data integrity.\n";
    }
};
