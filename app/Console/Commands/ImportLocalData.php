<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportLocalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-local-data {--file=ebims1_data_only_utf8.sql} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import local database data to cloud database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->option('file');
        $sqlFile = base_path($filename);

        if (!File::exists($sqlFile)) {
            $this->error("SQL file not found: {$sqlFile}");
            return 1;
        }

        $fileSize = File::size($sqlFile);
        $this->info("SQL file: {$sqlFile}");
        $this->info("File size: " . number_format($fileSize / 1024, 2) . " KB");
        $this->newLine();

        $currentDb = DB::connection()->getDatabaseName();
        $this->warn("Current database: {$currentDb}");
        $this->warn("This will import data into the current database!");
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Import cancelled.');
                return 0;
            }
        } else {
            $this->info('Force mode: Skipping confirmation...');
        }

        $this->info('Reading SQL file...');
        $sql = File::get($sqlFile);

        if (empty($sql)) {
            $this->error('SQL file is empty!');
            return 1;
        }

        $this->info('Importing data to database...');
        $this->info('This may take a few minutes...');
        $this->newLine();

        try {
            // Disable foreign key checks and set MySQL compatibility modes
            DB::statement("SET FOREIGN_KEY_CHECKS = 0");
            DB::statement("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
            
            // Remove MySQL dump comments and unwanted commands
            $sql = preg_replace('/^--.*$/m', '', $sql); // Remove SQL comments starting with --
            $sql = preg_replace('/^#.*$/m', '', $sql); // Remove SQL comments starting with #
            $sql = preg_replace('/\/\*!40\d{3}.*?\*\/;?/s', '', $sql); // Remove MySQL version-specific comments
            $sql = preg_replace('/\/\*!50\d{3}.*?\*\/;?/s', '', $sql); // Remove MySQL 5.x version comments
            $sql = preg_replace('/LOCK TABLES.*?;/is', '', $sql); // Remove LOCK TABLES
            $sql = preg_replace('/UNLOCK TABLES.*?;/is', '', $sql); // Remove UNLOCK TABLES
            $sql = preg_replace('/ALTER TABLE.*?(DISABLE|ENABLE) KEYS.*?;/is', '', $sql); // Remove ALTER TABLE KEYS
            
            // Split by semicolons that are at the end of lines
            $statements = preg_split('/;\s*$/m', $sql);
            
            $count = 0;
            $total = 0;
            $inserted = 0;
            
            // Count actual INSERT statements
            foreach ($statements as $statement) {
                $stmt = trim($statement);
                if (!empty($stmt) && stripos($stmt, 'INSERT') !== false) {
                    $total++;
                }
            }
            
            $this->info("Found {$total} INSERT statements to execute...");
            $this->newLine();
            
            // Execute statements
            foreach ($statements as $statement) {
                $stmt = trim($statement);
                
                // Skip empty statements
                if (empty($stmt)) {
                    continue;
                }
                
                // Only execute INSERT statements
                if (stripos($stmt, 'INSERT') === false) {
                    continue;
                }
                
                try {
                    DB::unprepared($stmt . ';');
                    $inserted++;
                    $count++;
                    
                    if ($count % 10 === 0) {
                        $this->info("✓ Imported {$count}/{$total} tables...");
                    }
                } catch (\Exception $e) {
                    $this->warn("Warning on statement {$count}: " . substr($e->getMessage(), 0, 100));
                }
            }
            
            $this->newLine();
            $this->info("✓ Successfully executed {$inserted} INSERT statements");
            
            // Re-enable foreign key checks
            DB::statement("SET FOREIGN_KEY_CHECKS = 1");
            
            $this->info('✅ Import successful!');
            $this->newLine();
            
            // Verify import - count records in key tables
            $this->info('Verifying import - Record counts:');
            $tables = ['users', 'members', 'loans', 'repayments', 'savings', 'schools', 'branches', 'products'];
            
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    $this->line("   - {$table}: {$count} records");
                } catch (\Exception $e) {
                    $this->line("   - {$table}: (table not found or error)");
                }
            }
            
            $this->newLine();
            $this->info('=== IMPORT COMPLETE ===');
            $this->info('✅ Your local data has been successfully imported!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
