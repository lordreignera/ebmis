<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileMoneyService
{
    private string $flexipayEndpoint;
    private int $timeout;
    private ?StanbicFlexiPayService $stanbicService = null;
    private string $provider;
    
    public function __construct(StanbicFlexiPayService $stanbicService = null)
    {
        $this->flexipayEndpoint = 'https://emuria.net/flexipay/marchanToMobilePayprod.php';
        // Increase timeout to 60 seconds for slow mobile money APIs
        // Can be configured via MOBILE_MONEY_TIMEOUT env variable
        $this->timeout = env('MOBILE_MONEY_TIMEOUT', 60);
        $this->stanbicService = $stanbicService ?? new StanbicFlexiPayService();
        $this->provider = env('MOBILE_MONEY_PROVIDER', 'stanbic'); // 'stanbic' or 'emuria'
    }
    
    /**
     * Disburse money via mobile money
     */
    public function disburse(string $phone, float $amount, ?string $network = null, ?string $beneficiaryName = null, ?string $requestId = null): array
    {
        // Use Stanbic FlexiPay if configured
        if ($this->provider === 'stanbic' && config('stanbic_flexipay.enabled')) {
            return $this->disburseViaStanbic($phone, $amount, $network, $beneficiaryName, $requestId);
        }
        
        // Fallback to Emuria FlexiPay
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            // Detect network if not provided
            if (!$network) {
                $network = $this->detectNetwork($formattedPhone);
            }
            
            Log::info("Mobile Money Disbursement Request (Emuria)", [
                'original_phone' => $phone,
                'formatted_phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount
            ]);
            
            // Prepare request data using exact same format as bimsadmin legacy system
            $requestData = [
                'name' => $beneficiaryName ?? 'Name',
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
            
            Log::info("FlexiPay API Response (Emuria)", [
                'http_code' => $httpCode,
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);
            
            // Parse response
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData && isset($responseData['statusCode'])) {
                    return $this->processApiResponse($responseData, $formattedPhone, $amount);
                } else if ($responseData && isset($responseData['httpCode'])) {
                    // API returned an error with httpCode format (like auth failures)
                    $errorMessage = $responseData['httpMessage'] ?? 'API Error';
                    $moreInfo = isset($responseData['moreInformation']) ? ': ' . $responseData['moreInformation'] : '';
                    
                    return [
                        'success' => false,
                        'status_code' => 'API_ERROR',
                        'message' => $errorMessage . $moreInfo,
                        'raw_response' => $responseBody
                    ];
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
     * Disburse money via Stanbic FlexiPay
     */
    private function disburseViaStanbic(string $phone, float $amount, ?string $network = null, ?string $beneficiaryName = null, ?string $requestId = null): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->stanbicService->formatPhoneNumber($phone);
            
            // Detect network if not provided
            if (!$network) {
                $network = $this->stanbicService->detectNetwork($formattedPhone);
            }
            
            if (!$network) {
                return [
                    'success' => false,
                    'status_code' => 'INVALID_NETWORK',
                    'message' => 'Could not detect mobile network from phone number'
                ];
            }
            
            // Validate amount
            $validation = $this->stanbicService->validateAmount($amount, $network);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'status_code' => 'INVALID_AMOUNT',
                    'message' => $validation['message']
                ];
            }
            
            // Call Stanbic API with custom request ID for idempotent retries
            $result = $this->stanbicService->disburseMoney(
                $formattedPhone,
                $amount,
                $network,
                $beneficiaryName ?? 'Beneficiary',
                'Loan disbursement',
                $requestId  // Pass request ID for idempotency
            );
            
            // Check both HTTP success AND Stanbic's statusCode
            if ($result['success'] && isset($result['response']['statusCode'])) {
                $statusCode = $result['response']['statusCode'];
                $statusDesc = $result['response']['statusDescription'] ?? '';
                
                // Success codes: 00 (success), 01 (pending - accepted)
                if (in_array($statusCode, ['00', '01'])) {
                    return [
                        'success' => true,
                        'status_code' => $statusCode,
                        'message' => $statusDesc ?: 'Disbursement initiated successfully',
                        'reference' => $result['request_id'],
                        'phone' => $formattedPhone,
                        'amount' => $amount,
                        'network' => $network,
                        'provider' => 'stanbic'
                    ];
                } else {
                    // Failed with specific Stanbic error code
                    return [
                        'success' => false,
                        'status_code' => $statusCode,
                        'message' => $statusDesc ?: 'Disbursement failed',
                        'provider' => 'stanbic'
                    ];
                }
            } else if ($result['success']) {
                // HTTP success but no statusCode (shouldn't happen)
                return [
                    'success' => true,
                    'status_code' => '00',
                    'message' => 'Disbursement initiated successfully',
                    'reference' => $result['request_id'],
                    'phone' => $formattedPhone,
                    'amount' => $amount,
                    'network' => $network,
                    'provider' => 'stanbic'
                ];
            } else {
                // HTTP request failed - check for detailed error in response
                $errorMessage = 'Disbursement failed';
                
                if (isset($result['response']['moreInformation'])) {
                    $errorMessage = $result['response']['httpMessage'] . ': ' . $result['response']['moreInformation'];
                } elseif (isset($result['response']['httpMessage'])) {
                    $errorMessage = $result['response']['httpMessage'];
                } elseif (isset($result['error'])) {
                    $errorMessage = $result['error'];
                }
                
                return [
                    'success' => false,
                    'status_code' => 'ERROR',
                    'message' => $errorMessage,
                    'provider' => 'stanbic',
                    'raw_response' => json_encode($result['response'] ?? [])
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Stanbic Disbursement Error", [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'status_code' => 'EXCEPTION',
                'message' => 'Disbursement failed: ' . $e->getMessage(),
                'provider' => 'stanbic'
            ];
        }
    }
    
    /**
     * Send money to a mobile money account (disbursement)
     */
    public function sendMoney(string $recipientName, string $phone, float $amount, ?string $description = null): array
    {
        // Use Stanbic FlexiPay if configured
        if ($this->provider === 'stanbic' && config('stanbic_flexipay.enabled')) {
            // Use the existing disburseViaStanbic method with appropriate parameters
            // Pass null for requestId to let Stanbic generate proper format (EbP...)
            return $this->disburseViaStanbic($phone, $amount, null, $recipientName, null);
        }
        
        // Fallback to Emuria FlexiPay
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            // Detect network
            $network = $this->detectNetwork($formattedPhone);
            
            // Sanitize recipient name for API requirements
            $sanitizedName = $this->sanitizeName($recipientName);
            
            Log::info("Mobile Money Send Request (Emuria)", [
                'recipient_original' => $recipientName,
                'recipient_sanitized' => $sanitizedName,
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount,
                'description' => $description
            ]);
            
            // Prepare request data using exact FlexiPay format
            $requestData = [
                'name' => $sanitizedName,
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
                    $result = $this->processApiResponse($responseData, $formattedPhone, $amount, 'disbursement');
                    $result['reference'] = $responseData['flexipayReferenceNumber'] ?? 'REF-' . time();
                    return $result;
                } else if ($responseData && isset($responseData['httpCode'])) {
                    // API returned an error with httpCode format (like auth failures)
                    $errorMessage = $responseData['httpMessage'] ?? 'API Error';
                    $moreInfo = isset($responseData['moreInformation']) ? ': ' . $responseData['moreInformation'] : '';
                    
                    return [
                        'success' => false,
                        'status_code' => 'API_ERROR',
                        'message' => $errorMessage . $moreInfo,
                        'raw_response' => $responseBody,
                        'type' => 'disbursement'
                    ];
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
    public function collectMoney(string $payerName, string $phone, float $amount, ?string $description = null): array
    {
        // Use Stanbic FlexiPay if configured
        if ($this->provider === 'stanbic' && config('stanbic_flexipay.enabled')) {
            return $this->collectViaStanbic($phone, $amount, $description);
        }
        
        // Fallback to Emuria FlexiPay
        try {
            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            // Detect network
            $network = $this->detectNetwork($formattedPhone);
            
            // Sanitize payer name for API requirements
            $sanitizedName = $this->sanitizeName($payerName);
            
            Log::info("Mobile Money Collection Request (Emuria)", [
                'payer_original' => $payerName,
                'payer_sanitized' => $sanitizedName,
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount,
                'description' => $description
            ]);
            
            // Use the collection endpoint for receiving money from customers
            $collectionEndpoint = 'https://emuria.net/flexipay/marchanFromMobileProd.php';
            
            // Prepare request data for collection
            $requestData = [
                'phone' => $formattedPhone,
                'network' => $network,
                'amount' => $amount
            ];
            
            Log::info("FlexiPay Collection Request (Emuria)", [
                'request_data' => $requestData
            ]);
            
            // Make API request
            $response = Http::timeout($this->timeout)
                          ->asForm()
                          ->withoutVerifying()
                          ->post($collectionEndpoint, $requestData);
            
            $responseBody = $response->body();
            $httpCode = $response->status();
            
            Log::info("FlexiPay Collection Response (Emuria)", [
                'http_code' => $httpCode,
                'response_body' => $responseBody,
                'request_data' => $requestData
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData && isset($responseData['statusCode'])) {
                    $result = $this->processApiResponse($responseData, $formattedPhone, $amount, 'collection');
                    $result['reference'] = $responseData['transactionReferenceNumber'] ?? ('REF-' . time());
                    $result['flexipay_ref'] = $responseData['flexipayReferenceNumber'] ?? '';
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
            Log::error("Mobile Money Collection Error (Emuria)", [
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
     * Collect money via Stanbic FlexiPay
     */
    private function collectViaStanbic(string $phone, float $amount, ?string $description = null): array
    {
        try {
            // Format phone number
            $formattedPhone = $this->stanbicService->formatPhoneNumber($phone);
            
            // Detect network
            $network = $this->stanbicService->detectNetwork($formattedPhone);
            
            if (!$network) {
                return [
                    'success' => false,
                    'status_code' => 'INVALID_NETWORK',
                    'message' => 'Could not detect mobile network from phone number'
                ];
            }
            
            // Validate amount
            $validation = $this->stanbicService->validateAmount($amount, $network);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'status_code' => 'INVALID_AMOUNT',
                    'message' => $validation['message']
                ];
            }
            
            // Call Stanbic API
            $result = $this->stanbicService->collectMoney(
                $formattedPhone,
                $amount,
                $network,
                $description ?? 'Payment collection'
            );
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'status_code' => '00',
                    'message' => 'Collection initiated successfully',
                    'reference' => $result['request_id'],
                    'phone' => $formattedPhone,
                    'amount' => $amount,
                    'network' => $network,
                    'provider' => 'stanbic',
                    'type' => 'collection'
                ];
            } else {
                return [
                    'success' => false,
                    'status_code' => 'ERROR',
                    'message' => $result['error'] ?? 'Collection failed',
                    'provider' => 'stanbic',
                    'type' => 'collection'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Stanbic Collection Error", [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'status_code' => 'EXCEPTION',
                'message' => 'Collection failed: ' . $e->getMessage(),
                'provider' => 'stanbic',
                'type' => 'collection'
            ];
        }
    }
    
    /**
     * Check transaction status
     * 
     * @param string $transactionReference FlexiPay transaction reference
     * @param string|null $network Network code (MTN, AIRTEL) - required for Stanbic
     * @return array Status information
     */
    public function checkTransactionStatus(string $transactionReference, ?string $network = null): array
    {
        // Use Stanbic FlexiPay if configured
        if ($this->provider === 'stanbic' && config('stanbic_flexipay.enabled')) {
            return $this->checkStatusViaStanbic($transactionReference, $network);
        }
        
        // Fallback to Emuria FlexiPay
        try {
            $statusEndpoint = 'https://emuria.net/flexipay/checkFromMMStatusProd.php';
            
            $requestData = [
                'reference' => $transactionReference  // FIXED: FlexiPay expects 'reference' not 'transactionId'
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
                // FIXED: Use statusDescription text, not just code, as FlexiPay returns '01' for both success and failure
                $status = 'pending';
                $descriptionUpper = strtoupper($statusDescription);
                
                if (str_contains($descriptionUpper, 'SUCCESS') || str_contains($descriptionUpper, 'COMPLETED')) {
                    $status = 'completed';
                } elseif (str_contains($descriptionUpper, 'FAILED') || str_contains($descriptionUpper, 'DECLINED') || 
                          str_contains($descriptionUpper, 'CANCELLED') || str_contains($descriptionUpper, 'REJECTED')) {
                    $status = 'failed';
                } elseif (str_contains($descriptionUpper, 'PENDING') || str_contains($descriptionUpper, 'INITIATED')) {
                    $status = 'pending';
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
     * Check transaction status via Stanbic FlexiPay
     */
    private function checkStatusViaStanbic(string $transactionReference, ?string $network = null): array
    {
        try {
            // If network not provided, try to detect it from the transaction reference
            // For now, we'll try both networks since Stanbic requires network for status check
            if (!$network) {
                Log::warning("Network not provided for Stanbic status check, will try MTN first", [
                    'reference' => $transactionReference
                ]);
                $network = 'MTN'; // Default to MTN, will try AIRTEL if MTN fails
            }
            
            Log::info("Checking Stanbic transaction status", [
                'reference' => $transactionReference,
                'network' => $network
            ]);
            
            $result = $this->stanbicService->checkStatus($transactionReference, $network);
            
            if (!$result['success']) {
                // If MTN fails and we defaulted to it, try AIRTEL
                if ($network === 'MTN' && !$result['success']) {
                    Log::info("MTN status check failed, trying AIRTEL", [
                        'reference' => $transactionReference
                    ]);
                    $result = $this->stanbicService->checkStatus($transactionReference, 'AIRTEL');
                }
            }
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Status check failed',
                    'transaction_reference' => $transactionReference,
                    'raw_response' => $result['response'] ?? []
                ];
            }
            
            // Parse Stanbic response
            $response = $result['response'] ?? [];
            $statusCode = $response['statusCode'] ?? '';
            $statusDescription = $response['statusDescription'] ?? 'Unknown status';
            
            // Map Stanbic status to our internal status
            $status = 'pending';
            $descriptionUpper = strtoupper($statusDescription);
            
            // Stanbic status codes: 
            // 00 = Success/Completed
            // 01 = Pending/Processing  
            // 02 = Failed
            // 03 = User cancelled
            if ($statusCode === '00' || str_contains($descriptionUpper, 'SUCCESS') || str_contains($descriptionUpper, 'COMPLETED')) {
                $status = 'completed';
            } elseif (in_array($statusCode, ['02', '03']) || 
                      str_contains($descriptionUpper, 'FAILED') || 
                      str_contains($descriptionUpper, 'DECLINED') || 
                      str_contains($descriptionUpper, 'CANCELLED') || 
                      str_contains($descriptionUpper, 'REJECTED')) {
                $status = 'failed';
            }
            
            return [
                'success' => true,
                'status' => $status,
                'status_code' => $statusCode,
                'message' => $statusDescription,
                'transaction_reference' => $transactionReference,
                'raw_response' => $response
            ];
            
        } catch (\Exception $e) {
            Log::error("Stanbic status check error", [
                'reference' => $transactionReference,
                'network' => $network,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Status check failed: ' . $e->getMessage(),
                'transaction_reference' => $transactionReference
            ];
        }
    }
    
    /**
     * Process API response from FlexiPay
     */
    /**
     * Sanitize name for FlexiPay API
     * API requirements: max 128 characters, only alphabetic characters and spaces
     */
    private function sanitizeName(string $name): string
    {
        // Remove any non-alphabetic characters except spaces
        $sanitized = preg_replace('/[^a-zA-Z\s]/', '', $name);
        
        // Remove extra spaces and trim
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));
        
        // Limit to 128 characters (FlexiPay limit)
        if (strlen($sanitized) > 128) {
            $sanitized = substr($sanitized, 0, 128);
        }
        
        // If name is empty after sanitization, use default
        if (empty($sanitized)) {
            $sanitized = 'Customer';
        }
        
        return $sanitized;
    }
    
    private function processApiResponse(array $responseData, string $phone, float $amount, string $type = 'disbursement'): array
    {
        $statusCode = $responseData['statusCode'] ?? '';
        $statusDescription = $responseData['statusDescription'] ?? 'Unknown status';
        $requestId = $responseData['requestId'] ?? '';
        $flexipayRef = $responseData['flexipayReferenceNumber'] ?? '';
        
        // For collections (repayments), treat any non-error response as successful initiation
        // The actual payment confirmation will be checked via CheckTransactions
        // Status codes: 00=success, 01=pending/processing, 02/03=failed
        if ($type === 'collection') {
            // For collections, consider 00 and 01 as successful initiation
            $isSuccessful = in_array($statusCode, ['00', '01']) || !empty($requestId);
        } else {
            // For disbursements, consider 00 (success) and 01 (processing) as successful
            // because the money is being sent even if status is "processing"
            $isSuccessful = in_array($statusCode, ['00', '01']) || 
                            !empty($requestId) || 
                            stripos($statusDescription, 'received and is being processed') !== false ||
                            stripos($statusDescription, 'processing') !== false;
        }
        
        $result = [
            'success' => $isSuccessful,
            'status_code' => $statusCode,
            'message' => $statusDescription,
            'request_id' => $requestId,
            'flexipay_reference' => $flexipayRef,
            'transaction_reference' => $flexipayRef . '_' . $requestId,
            'phone' => $phone,
            'amount' => $amount,
            'timestamp' => now(),
            'type' => $type
        ];
        
        // Log transaction details
        if ($isSuccessful) {
            Log::info("Mobile Money {$type} Successful/Initiated", $result);
        } else {
            Log::warning("Mobile Money {$type} Failed", $result);
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