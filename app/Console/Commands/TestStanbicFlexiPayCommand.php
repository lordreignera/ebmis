<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StanbicFlexiPayService;

class TestStanbicFlexiPayCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stanbic:test
                            {--phone=256777123456 : Test phone number}
                            {--amount=1000 : Test amount (UGX)}
                            {--type=collection : Test type (collection or disbursement)}
                            {--name=Test User : Beneficiary name for disbursement}';

    /**
     * The console command description.
     */
    protected $description = 'Test Stanbic Bank FlexiPay API connection and mobile money functionality';

    protected StanbicFlexiPayService $stanbicService;

    public function __construct(StanbicFlexiPayService $stanbicService)
    {
        parent::__construct();
        $this->stanbicService = $stanbicService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏦 Stanbic Bank FlexiPay API Test');
        $this->info('====================================');
        $this->line('');

        // Check configuration
        if (empty(config('stanbic_flexipay.client_id')) || empty(config('stanbic_flexipay.client_secret'))) {
            $this->error('❌ Stanbic FlexiPay credentials not configured.');
            $this->line('Please check your .env file for:');
            $this->line('  - STANBIC_CLIENT_ID');
            $this->line('  - STANBIC_CLIENT_SECRET');
            $this->line('  - STANBIC_MERCHANT_CODE');
            return 1;
        }

        $this->info('✅ Stanbic FlexiPay credentials configured');
        $this->line('');

        // Display configuration
        $this->info('Configuration:');
        $this->table(['Setting', 'Value'], [
            ['Base URL', config('stanbic_flexipay.base_url')],
            ['Merchant Code', config('stanbic_flexipay.merchant_code')],
            ['Client Name', config('stanbic_flexipay.client_name')],
            ['Enabled', config('stanbic_flexipay.enabled') ? '✅ Yes' : '❌ No'],
            ['Test Mode', config('stanbic_flexipay.test_mode') ? '✅ Enabled' : '❌ Disabled'],
            ['Timeout', config('stanbic_flexipay.timeout') . ' seconds'],
        ]);

        $this->line('');

        // Test connection
        $this->info('🔄 Testing API connection...');
        $connectionTest = $this->stanbicService->testConnection();
        
        if ($connectionTest['connection']) {
            $this->info('✅ ' . $connectionTest['message']);
            $this->line('✅ OAuth token generated successfully');
        } else {
            $this->error('❌ ' . $connectionTest['message']);
            return 1;
        }

        $this->line('');

        // Test phone number formatting and network detection
        $phone = $this->option('phone');
        $amount = (float) $this->option('amount');
        $type = $this->option('type');
        $beneficiaryName = $this->option('name');

        $this->info('Testing phone number formatting...');
        $formattedPhone = $this->stanbicService->formatPhoneNumber($phone);
        $network = $this->stanbicService->detectNetwork($formattedPhone);

        $this->table(['Property', 'Value'], [
            ['Original Phone', $phone],
            ['Formatted Phone', $formattedPhone],
            ['Detected Network', $network ?? '❌ Unknown'],
        ]);

        if (!$network) {
            $this->error('❌ Could not detect network from phone number');
            $this->line('Supported prefixes:');
            foreach ($this->stanbicService->getSupportedNetworks() as $code => $info) {
                $this->line("  {$code}: " . implode(', ', $info['prefixes']));
            }
            return 1;
        }

        $this->info('✅ Network detected: ' . $network);
        $this->line('');

        // Validate amount
        $this->info('Testing amount validation...');
        $validation = $this->stanbicService->validateAmount($amount, $network);
        
        if ($validation['valid']) {
            $this->info('✅ Amount is valid for ' . $network);
        } else {
            $this->error('❌ ' . $validation['message']);
            
            $limits = config('stanbic_flexipay.limits');
            if (isset($limits[$network])) {
                $this->line($network . ' limits: ' . number_format($limits[$network]['min']) . ' - ' . number_format($limits[$network]['max']) . ' UGX');
            }
            return 1;
        }

        $this->line('');

        // Ask for confirmation before making real API call
        $this->warn('⚠️  WARNING: This will make a REAL API call!');
        $this->line('');
        
        if ($type === 'collection') {
            $this->line("Type: Money Collection (Customer pays you)");
            $this->line("Phone: {$formattedPhone}");
            $this->line("Network: {$network}");
            $this->line("Amount: " . number_format($amount) . " UGX");
            $this->line('');
            $this->warn('A USSD prompt will be sent to the phone number.');
        } else {
            $this->line("Type: Money Disbursement (You pay customer)");
            $this->line("Phone: {$formattedPhone}");
            $this->line("Network: {$network}");
            $this->line("Beneficiary: {$beneficiaryName}");
            $this->line("Amount: " . number_format($amount) . " UGX");
            $this->line('');
            $this->warn('Money will be sent from your Stanbic merchant account.');
        }

        if (!$this->confirm('Do you want to proceed with this transaction?', false)) {
            $this->info('Test cancelled.');
            return 0;
        }

        $this->line('');
        $this->info('🔄 Processing transaction...');

        // Perform the actual transaction
        if ($type === 'collection') {
            $result = $this->stanbicService->collectMoney(
                $formattedPhone,
                $amount,
                $network,
                'Test collection payment'
            );
        } else {
            $result = $this->stanbicService->disburseMoney(
                $formattedPhone,
                $amount,
                $network,
                $beneficiaryName,
                'Test disbursement payment'
            );
        }

        $this->line('');

        // Display results
        if ($result['success']) {
            $this->info('✅ Transaction initiated successfully!');
            $this->line('');
            $this->table(['Property', 'Value'], [
                ['Request ID', $result['request_id']],
                ['Status', 'Initiated'],
                ['Response', json_encode($result['response'], JSON_PRETTY_PRINT)],
            ]);
            
            $this->line('');
            if ($type === 'collection') {
                $this->info('📱 Check the phone for USSD prompt to complete payment');
            } else {
                $this->info('💰 Money is being transferred to the beneficiary');
            }
            
            $this->line('');
            $this->line('To check transaction status, use:');
            $this->line("  php artisan stanbic:check-status {$result['request_id']} {$network}");
            
        } else {
            $this->error('❌ Transaction failed!');
            $this->line('');
            $this->table(['Property', 'Value'], [
                ['Error', $result['error'] ?? 'Unknown error'],
                ['Response', isset($result['response']) ? json_encode($result['response'], JSON_PRETTY_PRINT) : 'N/A'],
            ]);
        }

        return $result['success'] ? 0 : 1;
    }
}
