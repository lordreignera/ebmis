<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConfigureFlexiPayCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'flexipay:configure
                            {--merchant-code= : FlexiPay merchant code}
                            {--secret-key= : FlexiPay secret key}
                            {--test-mode=true : Enable test mode (true/false)}';

    /**
     * The console command description.
     */
    protected $description = 'Configure FlexiPay API credentials for mobile money integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('FlexiPay API Configuration');
        $this->info('========================');

        // Get current .env file content
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            $this->error('.env file not found. Please copy .env.example to .env first.');
            return 1;
        }

        $envContent = File::get($envPath);

        // Get configuration values
        $merchantCode = $this->option('merchant-code') ?: $this->ask('Enter your FlexiPay Merchant Code');
        $secretKey = $this->option('secret-key') ?: $this->secret('Enter your FlexiPay Secret Key');
        $testMode = $this->option('test-mode') !== null ? $this->option('test-mode') : $this->confirm('Enable test mode?', true);

        if (empty($merchantCode) || empty($secretKey)) {
            $this->error('Merchant code and secret key are required.');
            return 1;
        }

        // Update .env file
        $updates = [
            'FLEXIPAY_MERCHANT_CODE' => $merchantCode,
            'FLEXIPAY_SECRET_KEY' => $secretKey,
            'MOBILE_MONEY_TEST_MODE' => $testMode ? 'true' : 'false',
            'FLEXIPAY_ENABLED' => 'true',
        ];

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        // Write updated content back to .env
        File::put($envPath, $envContent);

        $this->info('✅ FlexiPay configuration updated successfully!');
        $this->line('');
        $this->table(['Setting', 'Value'], [
            ['Merchant Code', str_repeat('*', strlen($merchantCode) - 4) . substr($merchantCode, -4)],
            ['Secret Key', str_repeat('*', 20)],
            ['Test Mode', $testMode ? 'Enabled' : 'Disabled'],
            ['API URL', config('flexipay.api_url')],
        ]);

        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Run: php artisan config:cache');
        $this->line('2. Test the connection: php artisan flexipay:test');
        $this->line('3. Configure your callback URL in FlexiPay dashboard:');
        $this->line('   ' . config('flexipay.callback_url'));

        return 0;
    }
}