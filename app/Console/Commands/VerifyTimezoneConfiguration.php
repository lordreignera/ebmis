<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VerifyTimezoneConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'timezone:verify';

    /**
     * The console command description.
     */
    protected $description = 'Verify that timezone configuration is correctly set for East African Time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying Timezone Configuration for East African Time (EAT)');
        $this->newLine();

        // Check PHP timezone
        $phpTimezone = date_default_timezone_get();
        $this->line("PHP Timezone: {$phpTimezone}");

        // Check Laravel config timezone
        $appTimezone = config('app.timezone');
        $this->line("Laravel App Timezone: {$appTimezone}");

        // Check Carbon timezone
        $carbonTimezone = Carbon::now()->getTimezone()->getName();
        $this->line("Carbon Timezone: {$carbonTimezone}");

        // Check MySQL timezone
        try {
            $mysqlTimezone = DB::selectOne('SELECT @@time_zone as timezone');
            $this->line("MySQL Timezone: {$mysqlTimezone->timezone}");
        } catch (\Exception $e) {
            $this->error("Could not get MySQL timezone: " . $e->getMessage());
        }

        $this->newLine();
        
        // Show current times
        $this->info('Current Times:');
        $utcNow = Carbon::now('UTC');
        $eatNow = Carbon::now('Africa/Nairobi');
        
        $this->line("UTC Time: {$utcNow->format('Y-m-d H:i:s T')}");
        $this->line("EAT Time: {$eatNow->format('Y-m-d H:i:s T')}");
        
        $this->newLine();
        
        // Verify configuration
        $errors = [];
        
        if ($phpTimezone !== 'Africa/Nairobi') {
            $errors[] = "PHP timezone should be 'Africa/Nairobi', but is '{$phpTimezone}'";
        }
        
        if ($appTimezone !== 'Africa/Nairobi') {
            $errors[] = "Laravel timezone should be 'Africa/Nairobi', but is '{$appTimezone}'";
        }
        
        if (isset($mysqlTimezone) && $mysqlTimezone->timezone !== '+03:00') {
            $errors[] = "MySQL timezone should be '+03:00', but is '{$mysqlTimezone->timezone}'";
        }
        
        if (empty($errors)) {
            $this->info('✅ All timezone configurations are correct!');
        } else {
            $this->error('❌ Timezone configuration issues found:');
            foreach ($errors as $error) {
                $this->line("   • {$error}");
            }
            $this->newLine();
            $this->info('Run the following commands to fix:');
            $this->line('   php artisan migrate (for MySQL timezone)');
            $this->line('   php artisan config:cache (to refresh config)');
        }

        return 0;
    }
}
