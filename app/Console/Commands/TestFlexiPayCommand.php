<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MobileMoneyService;

class TestFlexiPayCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'flexipay:test
                            {--amount=1000 : Test amount (UGX)}
                            {--phone=256777123456 : Test phone number}';

    /**
     * The console command description.
     */
    protected $description = 'Test FlexiPay API connection and mobile money functionality';

    protected MobileMoneyService $mobileMoneyService;

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        parent::__construct();
        $this->mobileMoneyService = $mobileMoneyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('FlexiPay API Connection Test');
        $this->info('===========================');

        // Check configuration
        if (empty(config('flexipay.merchant_code')) || empty(config('flexipay.secret_key'))) {
            $this->error('❌ FlexiPay credentials not configured.');
            $this->line('Run: php artisan flexipay:configure');
            return 1;
        }

        $this->info('✅ FlexiPay credentials configured');

        // Test configuration
        $this->line('');
        $this->info('Configuration:');
        $this->table(['Setting', 'Value'], [
            ['API URL', config('flexipay.api_url')],
            ['Merchant Code', str_repeat('*', strlen(config('flexipay.merchant_code')) - 4) . substr(config('flexipay.merchant_code'), -4)],
            ['Test Mode', config('flexipay.test_mode') ? '✅ Enabled' : '❌ Disabled'],
            ['Callback URL', config('flexipay.callback_url')],
            ['Timeout', config('flexipay.timeout') . ' seconds'],
        ]);

        // Test phone number formatting
        $this->line('');
        $this->info('Testing phone number formatting...');
        $testPhone = $this->option('phone');
        $formatted = $this->mobileMoneyService->formatPhoneNumber($testPhone);
        $this->line("Original: {$testPhone}");
        $this->line("Formatted: {$formatted}");

        // Test network detection
        $this->info('Testing network detection...');
        $network = $this->mobileMoneyService->detectNetwork($formatted);
        $this->line("Detected network: {$network}");

        // Test basic connection
        $this->line('');
        $this->info('Testing API connection...');
        
        try {
            $result = $this->mobileMoneyService->testConnection();
            
            if ($result['success']) {
                $this->info('✅ API connection successful');
                if (isset($result['response_time'])) {
                    $this->line("Response time: {$result['response_time']}ms");
                }
            } else {
                $this->error('❌ API connection failed');
                $this->line("Error: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('❌ Connection test failed with exception:');
            $this->line($e->getMessage());
        }

        // Test validation
        $this->line('');
        $this->info('Testing transaction validation...');
        $amount = $this->option('amount');
        
        $validation = $this->mobileMoneyService->validateTransaction($formatted, $amount);
        if ($validation['valid']) {
            $this->info('✅ Transaction validation passed');
        } else {
            $this->error('❌ Transaction validation failed: ' . $validation['message']);
        }

        // Network-specific limits
        $this->line('');
        $this->info('Network limits:');
        $limits = config('flexipay.limits');
        
        if ($network === 'MTN') {
            $this->line("MTN limits: " . number_format($limits['mtn']['min']) . " - " . number_format($limits['mtn']['max']) . " UGX");
        } elseif ($network === 'AIRTEL') {
            $this->line("Airtel limits: " . number_format($limits['airtel']['min']) . " - " . number_format($limits['airtel']['max']) . " UGX");
        }

        $this->line('');
        $this->info('🎉 FlexiPay test completed!');
        
        if (config('flexipay.test_mode')) {
            $this->line('');
            $this->warn('⚠️  Test mode is enabled. Disable for production:');
            $this->line('Set MOBILE_MONEY_TEST_MODE=false in .env file');
        }

        return 0;
    }
}