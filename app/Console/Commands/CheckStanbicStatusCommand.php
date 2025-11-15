<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StanbicFlexiPayService;

class CheckStanbicStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stanbic:check-status
                            {request-id : The transaction request ID}
                            {network : The network (MTN or AIRTEL)}';

    /**
     * The console command description.
     */
    protected $description = 'Check the status of a Stanbic FlexiPay transaction';

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
        $requestId = $this->argument('request-id');
        $network = strtoupper($this->argument('network'));

        $this->info('🔍 Checking Stanbic Transaction Status');
        $this->info('======================================');
        $this->line('');

        $this->table(['Property', 'Value'], [
            ['Request ID', $requestId],
            ['Network', $network],
        ]);

        $this->line('');
        $this->info('🔄 Querying Stanbic API...');

        // Check status
        $result = $this->stanbicService->checkStatus($requestId, $network);

        $this->line('');

        if ($result['success']) {
            $this->info('✅ Status retrieved successfully!');
            $this->line('');
            
            $response = $result['response'];
            
            $this->table(['Property', 'Value'], [
                ['Request ID', $requestId],
                ['Status Code', $response['statusCode'] ?? 'N/A'],
                ['Status Description', $response['statusDescription'] ?? 'N/A'],
                ['Transaction Reference', $response['transactionReferenceNumber'] ?? 'N/A'],
                ['FlexiPay Reference', $response['flexipayReferenceNumber'] ?? 'N/A'],
                ['Amount', isset($response['amount']) ? number_format($response['amount']) . ' UGX' : 'N/A'],
            ]);

            $this->line('');
            $this->line('Full Response:');
            $this->line(json_encode($response, JSON_PRETTY_PRINT));

        } else {
            $this->error('❌ Failed to retrieve status!');
            $this->line('');
            $this->line('Error: ' . ($result['error'] ?? 'Unknown error'));
            
            if (isset($result['response'])) {
                $this->line('');
                $this->line('Response:');
                $this->line(json_encode($result['response'], JSON_PRETTY_PRINT));
            }
        }

        return $result['success'] ? 0 : 1;
    }
}
