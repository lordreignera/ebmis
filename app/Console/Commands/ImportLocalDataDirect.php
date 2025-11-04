<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportLocalDataDirect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import-direct 
                            {--file=ebims1_complete.sql : The SQL file to import}
                            {--force : Skip confirmation}
                            {--drop-tables : Drop all tables before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import local database data using MySQL command line';

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

        $this->info('Importing data using MySQL command line...');
        $this->info('This may take a few minutes...');
        $this->newLine();

        try {
            // Get database connection details
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Drop all tables if requested
            if ($this->option('drop-tables')) {
                $this->warn('Dropping all existing tables...');
                
                // Get all table names
                $tables = DB::select('SHOW TABLES');
                $tableKey = 'Tables_in_' . $database;
                
                if (!empty($tables)) {
                    // Disable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    
                    foreach ($tables as $table) {
                        $tableName = $table->$tableKey;
                        $this->line("  Dropping table: {$tableName}");
                        DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
                    }
                    
                    // Re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    
                    $this->info('✅ All tables dropped successfully!');
                    $this->newLine();
                }
            }

            // Build mysql command with init-command to disable foreign key checks
            // This ensures the SQL file can create tables without FK constraint errors
            $command = sprintf(
                'mysql -h%s -P%d -u%s -p%s --default-character-set=utf8mb4 --init-command="SET FOREIGN_KEY_CHECKS=0;" --force %s < %s 2>&1',
                escapeshellarg($host),
                $port,
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );

            $this->info('Executing: mysql import command...');
            
            // Execute the command
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // MySQL import with --force returns 0 even with warnings
            if (!empty($output)) {
                $this->warn('Import output:');
                foreach ($output as $line) {
                    if (stripos($line, 'error') !== false) {
                        $this->error('  ' . $line);
                    } else {
                        $this->line('  ' . $line);
                    }
                }
                $this->newLine();
            }
            
            $this->info('✅ Import command completed!');
            $this->newLine();
            
            // Verify import - count records in key tables
            $this->info('Verifying import - Record counts:');
            $tables = ['users', 'members', 'loans', 'repayments', 'savings', 'schools', 'branches', 'products', 'accounts_ledger', 'agency'];
            
            $totalRecords = 0;
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    $this->line("   - {$table}: {$count} records");
                    $totalRecords += $count;
                } catch (\Exception $e) {
                    $this->line("   - {$table}: (table doesn't exist)");
                }
            }
            
            $this->newLine();
            if ($totalRecords > 0) {
                $this->info("✅ SUCCESS! Imported {$totalRecords} total records across all tables");
                $this->info('=== IMPORT COMPLETE ===');
                return 0;
            } else {
                $this->error('⚠ WARNING: No records were imported. Check the error messages above.');
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
