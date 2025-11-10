<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileMoneyService
{
    private string $flexipayEndpoint;
    private int $timeout;
    
    public function __construct()
    {
        $this->flexipayEndpoint = 'https://emuria.net/flexipay/marchanToMobilePayprod.php';
        $this->timeout = 30;
    }
    
    /**
     * Disburse money via mobile money
     */
    public function disburse(string $phone, float $amount, string $network = null): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            // Detect network if not provided
            if (!$network) {
                $network = $this->detectNetwork($formattedPhone);
            }
            
            Log::info("Mobile Money Disbursement Request", [
                'original_phone' => $phone,
                'formatted_phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount
            ]);
            
            // Prepare request data using exact same format as bimsadmin legacy system
            $requestData = [
                'name' => 'Name',  // Static name as used in bimsadmin
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount
            ];
            
            // Make API request
            $response = Http::timeout($this->timeout)
                          ->asForm()
                          ->withoutVerifying() // Disable SSL verification for this endpoint
                          ->post($this->flexipayEndpoint, $requestData);
            
            $responseBody = $response->body();
            $httpCode = $response->status();
            
            Log::info("FlexiPay API Response", [
                'http_code' => $httpCode,
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);
            
            // Parse response
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData && isset($responseData['statusCode'])) {
                    return $this->processApiResponse($responseData, $formattedPhone, $amount);
                } else {
                    return [
                        'success' => false,
                        'status_code' => 'ERROR',
                        'message' => 'Invalid response format from payment gateway',
                        'raw_response' => $responseBody
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'status_code' => 'HTTP_ERROR',
                    'message' => "HTTP Error: {$httpCode}",
                    'raw_response' => $responseBody
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Mobile Money Disbursement Error", [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'status_code' => 'EXCEPTION',
                'message' => 'Disbursement failed: ' . $e->getMessage(),
                'error_details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send money to a mobile money account (disbursement)
     */
    public function sendMoney(string $recipientName, string $phone, float $amount, string $description = null): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            // Detect network
            $network = $this->detectNetwork($formattedPhone);
            
            Log::info("Mobile Money Send Request", [
                'recipient' => $recipientName,
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount,
                'description' => $description
            ]);
            
            // Prepare request data using exact FlexiPay format
            $requestData = [
                'name' => $recipientName,
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount
            ];
            
            // Make API request
            $response = Http::timeout($this->timeout)
                          ->asForm()
                          ->withoutVerifying()
                          ->post($this->flexipayEndpoint, $requestData);
            
            $responseBody = $response->body();
            $httpCode = $response->status();
            
            Log::info("FlexiPay Send Response", [
                'http_code' => $httpCode,
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData && isset($responseData['statusCode'])) {
                    $result = $this->processApiResponse($responseData, $formattedPhone, $amount);
                    $result['type'] = 'disbursement';
                    $result['reference'] = $responseData['flexipayReferenceNumber'] ?? 'REF-' . time();
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'status_code' => 'ERROR',
                        'message' => 'Invalid response format from payment gateway',
                        'raw_response' => $responseBody,
                        'type' => 'disbursement'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'status_code' => 'HTTP_ERROR',
                    'message' => "HTTP Error: {$httpCode}",
                    'raw_response' => $responseBody,
                    'type' => 'disbursement'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Mobile Money Send Error", [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'status_code' => 'EXCEPTION',
                'message' => 'Send money failed: ' . $e->getMessage(),
                'type' => 'disbursement'
            ];
        }
    }
    
    /**
     * Collect money from a mobile money account (repayment collection)
     */
    public function collectMoney(string $payerName, string $phone, float $amount, string $description = null): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            // Detect network
            $network = $this->detectNetwork($formattedPhone);
            
            Log::info("Mobile Money Collection Request", [
                'payer' => $payerName,
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount,
                'description' => $description
            ]);
            
            // Use the collection endpoint for receiving money from customers
            $collectionEndpoint = 'https://emuria.net/flexipay/marchanFromMobileProd.php';
            
            // Prepare request data for collection
            $requestData = [
                'name' => $payerName,
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount
            ];
            
            // Make API request
            $response = Http::timeout($this->timeout)
                          ->asForm()
                          ->withoutVerifying()
                          ->post($collectionEndpoint, $requestData);
            
            $responseBody = $response->body();
            $httpCode = $response->status();
            
            Log::info("FlexiPay Collection Response", [
                'http_code' => $httpCode,
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData && isset($responseData['statusCode'])) {
                    $result = $this->processApiResponse($responseData, $formattedPhone, $amount);
                    $result['type'] = 'collection';
                    $result['reference'] = $responseData['flexipayReferenceNumber'] ?? 'REF-' . time();
                    return $result;
                } else {
                    return [
                        'success' => false,
                        'status_code' => 'ERROR',
                        'message' => 'Invalid response format from payment gateway',
                        'raw_response' => $responseBody,
                        'type' => 'collection'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'status_code' => 'HTTP_ERROR',
                    'message' => "HTTP Error: {$httpCode}",
                    'raw_response' => $responseBody,
                    'type' => 'collection'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Mobile Money Collection Error", [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'status_code' => 'EXCEPTION',
                'message' => 'Collect money failed: ' . $e->getMessage(),
                'type' => 'collection'
            ];
        }
    }
    
    /**
     * Check transaction status
     */
    public function checkTransactionStatus(string $transactionReference): array
    {
        try {
            $statusEndpoint = 'https://emuria.net/flexipay/checkFromMMStatusProd.php';
            
            $requestData = [
                'transactionId' => $transactionReference
            ];
            
            $response = Http::timeout($this->timeout)
                          ->asForm()
                          ->withoutVerifying()
                          ->post($statusEndpoint, $requestData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                $statusCode = $responseData['statusCode'] ?? '';
                $statusDescription = $responseData['statusDescription'] ?? 'Unknown status';
                
                // Map status codes to our internal status
                $status = 'pending';
                if ($statusCode === '01' || $statusCode === '00') {
                    $status = 'completed';
                } elseif ($statusCode === '02' || $statusCode === '99') {
                    $status = 'failed';
                }
                
                return [
                    'success' => true,
                    'status' => $status,
                    'status_code' => $statusCode,
                    'message' => $statusDescription,
                    'transaction_reference' => $transactionReference,
                    'raw_response' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to check transaction status',
                    'transaction_reference' => $transactionReference
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Transaction status check error", [
                'reference' => $transactionReference,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Status check failed: ' . $e->getMessage(),
                'transaction_reference' => $transactionReference
            ];
        }
    }
    
    /**
     * Process API response from FlexiPay
     */
    private function processApiResponse(array $responseData, string $phone, float $amount): array
    {
        $statusCode = $responseData['statusCode'] ?? '';
        $statusDescription = $responseData['statusDescription'] ?? 'Unknown status';
        $requestId = $responseData['requestId'] ?? '';
        $flexipayRef = $responseData['flexipayReferenceNumber'] ?? '';
        
        // Check if successful
        $isSuccessful = ($statusCode === '00');
        
        $result = [
            'success' => $isSuccessful,
            'status_code' => $statusCode,
            'message' => $statusDescription,
            'request_id' => $requestId,
            'flexipay_reference' => $flexipayRef,
            'transaction_reference' => $flexipayRef . '_' . $requestId,
            'phone' => $phone,
            'amount' => $amount,
            'timestamp' => now()
        ];
        
        // Log transaction details
        if ($isSuccessful) {
            Log::info("Mobile Money Disbursement Successful", $result);
        } else {
            Log::warning("Mobile Money Disbursement Failed", $result);
        }
        
        return $result;
    }
    
    /**
     * Format phone number for API
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle different phone number formats
        if (substr($cleanPhone, 0, 3) === "256") {
            // Already has country code
            return $cleanPhone;
        } elseif (substr($cleanPhone, 0, 1) === "0") {
            // Remove leading 0 and add country code
            return "256" . substr($cleanPhone, 1);
        } elseif (substr($cleanPhone, 0, 1) === "7") {
            // Add country code to 7-digit number
            return "256" . $cleanPhone;
        } else {
            // Fallback: use cleaned phone as-is
            return $cleanPhone;
        }
    }
    
    /**
     * Detect mobile network from phone number
     */
    public function detectNetwork(string $formattedPhone): string
    {
        // MTN prefixes: 77, 78, 76
        if (in_array(substr($formattedPhone, 3, 2), ['77', '78', '76'])) {
            return 'MTN';
        }
        
        // Airtel prefixes: 70, 75, 74
        if (in_array(substr($formattedPhone, 3, 2), ['70', '75', '74'])) {
            return 'AIRTEL';
        }
        
        // Default to MTN if detection fails
        return 'MTN';
    }
    
    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): array
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        
        // Basic validation
        if (strlen($formattedPhone) < 12) {
            return [
                'valid' => false,
                'message' => 'Phone number too short',
                'formatted_phone' => $formattedPhone
            ];
        }
        
        if (strlen($formattedPhone) > 13) {
            return [
                'valid' => false,
                'message' => 'Phone number too long',
                'formatted_phone' => $formattedPhone
            ];
        }
        
        if (!str_starts_with($formattedPhone, '256')) {
            return [
                'valid' => false,
                'message' => 'Invalid country code',
                'formatted_phone' => $formattedPhone
            ];
        }
        
        // Check for valid network prefixes
        $prefix = substr($formattedPhone, 3, 2);
        $validPrefixes = ['77', '78', '76', '70', '75', '74'];
        
        if (!in_array($prefix, $validPrefixes)) {
            return [
                'valid' => false,
                'message' => 'Invalid network prefix',
                'formatted_phone' => $formattedPhone
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Valid phone number',
            'formatted_phone' => $formattedPhone,
            'network' => $this->detectNetwork($formattedPhone)
        ];
    }
    
    /**
     * Check transaction status (for callback verification)
     */
    public function verifyTransaction(string $transactionReference): array
    {
        // This would typically call a status check endpoint
        // For now, return a placeholder response
        return [
            'transaction_reference' => $transactionReference,
            'status' => 'PENDING',
            'message' => 'Status check not yet implemented'
        ];
    }
    
    /**
     * Process payment callback from FlexiPay
     */
    public function processCallback(array $callbackData): array
    {
        try {
            Log::info("Processing FlexiPay Callback", $callbackData);
            
            $transactionRef = $callbackData['transactionReferenceNumber'] ?? null;
            $statusCode = $callbackData['statusCode'] ?? null;
            $statusDesc = $callbackData['statusDescription'] ?? 'Unknown';
            
            if (!$transactionRef) {
                return [
                    'success' => false,
                    'message' => 'Missing transaction reference number',
                    'callback_data' => $callbackData
                ];
            }
            
            $isSuccessful = in_array($statusCode, ['00', '01']);
            
            $result = [
                'success' => $isSuccessful,
                'transaction_reference' => $transactionRef,
                'status_code' => $statusCode,
                'status_description' => $statusDesc,
                'callback_data' => $callbackData,
                'processed_at' => now()
            ];
            
            Log::info("FlexiPay Callback Processed", $result);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("FlexiPay Callback Processing Error", [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            
            return [
                'success' => false,
                'message' => 'Callback processing failed: ' . $e->getMessage(),
                'callback_data' => $callbackData
            ];
        }
    }
    
    /**
     * Get supported networks
     */
    public function getSupportedNetworks(): array
    {
        return [
            'MTN' => [
                'name' => 'MTN Uganda',
                'prefixes' => ['77', '78', '76'],
                'code' => 'MTN'
            ],
            'AIRTEL' => [
                'name' => 'Airtel Uganda',
                'prefixes' => ['70', '75', '74'],
                'code' => 'AIRTEL'
            ]
        ];
    }
    
    /**
     * SAFE Test API connectivity - NO REAL TRANSACTIONS OR MONEY SENT
     * This method only validates configuration and does NOT make actual API calls
     */
    public function testConnection(): array
    {
        try {
            // SAFE TEST: Only validate configuration - NO API CALLS MADE
            
            // Check if endpoint URL is configured
            if (empty($this->flexipayEndpoint)) {
                return [
                    'connection' => false,
                    'message' => 'FlexiPay API URL not configured',
                    'safe_test' => true
                ];
            }
            
            // Check if URL format is valid
            if (!filter_var($this->flexipayEndpoint, FILTER_VALIDATE_URL)) {
                return [
                    'connection' => false,
                    'message' => 'Invalid FlexiPay API URL format',
                    'safe_test' => true
                ];
            }
            
            // SAFE CONFIGURATION TEST ONLY - NO MONEY SENT
            return [
                'connection' => true,
                'message' => 'Configuration appears valid - NO TRANSACTIONS MADE',
                'endpoint' => $this->flexipayEndpoint,
                'safe_test' => true,
                'warning' => 'This is a configuration test only - NO MONEY IS SENT'
            ];
            
        } catch (\Exception $e) {
            return [
                'connection' => false,
                'message' => 'Configuration test completed safely - NO MONEY SENT: ' . $e->getMessage(),
                'safe_test' => true
            ];
        }
    }
}