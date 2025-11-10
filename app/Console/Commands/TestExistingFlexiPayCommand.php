<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestExistingFlexiPayCommand extends Command
{
    protected $signature = 'flexipay:test-existing {--phone=256708356505} {--amount=1000}';

    protected $description = 'Test existing FlexiPay API connectivity with production endpoint';

    public function handle()
    {
        $this->info('🔄 Testing Existing FlexiPay Configuration...');
        
        $phone = $this->option('phone');
        $amount = $this->option('amount');
        
        // Use the exact same parameters as found in the legacy system
        $requestData = [
            'name' => 'Name',  // Exact same as bimsadmin legacy system
            'phone' => $phone,
            'network' => $this->detectNetworkLegacyFormat($phone),
            'amount' => $amount,
        ];
        
        $this->table(['Parameter', 'Value'], [
            ['API URL', config('flexipay.api_url')],
            ['Phone', $phone],
            ['Network', $requestData['network']],
            ['Amount', $amount],
        ]);
        
        $this->info('🚀 Sending API request...');
        
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->withOptions([
                    'verify' => false,  // Disable SSL verification for WAMP environment
                ])
                ->asForm()
                ->post(config('flexipay.api_url'), $requestData);

            $this->info('📡 Response received:');
            $this->line('Status Code: ' . $response->status());
            $this->line('Response Body: ' . $response->body());
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData) {
                    $this->info('✅ Valid JSON response received');
                    $this->table(['Field', 'Value'], [
                        ['Status Code', $responseData['statusCode'] ?? 'N/A'],
                        ['Status Description', $responseData['statusDescription'] ?? 'N/A'],
                        ['Request ID', $responseData['requestId'] ?? 'N/A'],
                        ['FlexiPay Reference', $responseData['flexipayReferenceNumber'] ?? 'N/A'],
                    ]);
                    
                    if (isset($responseData['statusCode'])) {
                        if ($responseData['statusCode'] === '00' || $responseData['statusCode'] === '01') {
                            $this->info('🎉 SUCCESS! FlexiPay API is working correctly!');
                            return 0;
                        } else {
                            $this->warn('⚠️  API responded but transaction was not successful');
                            $this->warn('Reason: ' . ($responseData['statusDescription'] ?? 'Unknown'));
                            $this->info('This may be due to account/network issues or test mode limitations');
                        }
                    }
                } else {
                    $this->error('❌ Invalid JSON response or empty response');
                    $this->error('JSON Error: ' . json_last_error_msg());
                }
            } else {
                $this->error('❌ API request failed with status: ' . $response->status());
                $this->error('Response: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            Log::error('FlexiPay test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        $this->warn('💡 If you see errors, the legacy system may be using different credentials');
        $this->info('Next steps:');
        $this->line('1. Check bimsadmin system logs for successful API calls');
        $this->line('2. Contact Emuria Networks at office@emuria.net for credentials');
        $this->line('3. Test with the legacy system to confirm it\'s still working');
        
        return 1;
    }
    
    private function detectNetworkLegacyFormat($phone)
    {
        // Use the exact same network detection logic as the bimsadmin legacy system
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check for MTN prefixes: 78, 77, 76 (same order as legacy)
        if (preg_match('/^256(78|77|76)/', $phone)) {
            return 'MTN';
        }
        
        // All other numbers default to AIRTEL (including 70, 75, 74, 71)
        return 'AIRTEL';
    }
}