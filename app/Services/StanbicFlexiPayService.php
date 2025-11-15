<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class StanbicFlexiPayService
{
    protected $config;
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $merchantCode;
    protected $clientName;
    protected $password;
    protected $privateKey;
    protected $timeout;

    public function __construct()
    {
        $this->config = config('stanbic_flexipay');
        $this->baseUrl = $this->config['base_url'];
        $this->clientId = $this->config['client_id'];
        $this->clientSecret = $this->config['client_secret'];
        $this->merchantCode = $this->config['merchant_code'];
        $this->clientName = $this->config['client_name'];
        $this->password = $this->config['password'];
        $this->privateKey = $this->config['private_key'];
        $this->timeout = $this->config['timeout'];
    }

    /**
     * Get OAuth access token (with caching)
     */
    protected function getAccessToken(): string
    {
        // Check if token is cached
        if ($this->config['token_cache']['enabled']) {
            $cacheKey = $this->config['token_cache']['cache_key'];
            $cachedToken = Cache::get($cacheKey);
            
            if ($cachedToken) {
                $this->log('info', 'Using cached OAuth token');
                return $cachedToken;
            }
        }

        // Generate new token
        $this->log('info', 'Requesting new OAuth token from Stanbic');
        
        $token = base64_encode($this->clientId . ':' . $this->clientSecret);
        
        try {
            $response = Http::timeout($this->timeout)
                ->withoutVerifying() // Disable SSL verification for development
                ->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . $token,
                ])
                ->asForm()
                ->post($this->baseUrl . $this->config['endpoints']['oauth_token'], [
                    'grant_type' => 'client_credentials',
                    'scope' => 'Create',
                ]);

            if (!$response->successful()) {
                $this->log('error', 'OAuth token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('OAuth authentication failed: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['access_token'])) {
                throw new Exception('Access token not found in response');
            }

            $accessToken = $data['access_token'];

            // Cache the token
            if ($this->config['token_cache']['enabled']) {
                $ttl = $this->config['token_cache']['ttl'];
                Cache::put($this->config['token_cache']['cache_key'], $accessToken, $ttl);
                $this->log('info', 'OAuth token cached successfully', ['ttl' => $ttl]);
            }

            return $accessToken;

        } catch (Exception $e) {
            $this->log('error', 'OAuth token generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate request signature using RSA private key
     */
    protected function generateSignature(array $payload): string
    {
        try {
            // Convert payload to JSON and remove whitespace
            $message = json_encode($payload);
            $message = preg_replace('/\s+/', '', $message);

            // Sign the message
            $signature = '';
            $success = openssl_sign(
                $message,
                $signature,
                $this->privateKey,
                OPENSSL_ALGO_SHA256
            );

            if (!$success) {
                throw new Exception('Failed to generate signature');
            }

            // Encode signature
            $encodedSignature = base64_encode($signature);

            $this->log('debug', 'Signature generated successfully');

            return $encodedSignature;

        } catch (Exception $e) {
            $this->log('error', 'Signature generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make authenticated API request to Stanbic
     */
    protected function makeRequest(string $endpoint, array $payload): array
    {
        try {
            // Get access token
            $accessToken = $this->getAccessToken();

            // Generate signature
            $signature = $this->generateSignature($payload);

            // Prepare message
            $message = json_encode($payload);
            $message = preg_replace('/\s+/', '', $message);

            // Make request
            $url = $this->baseUrl . $endpoint;
            
            $this->log('info', 'Making Stanbic API request', [
                'url' => $url,
                'payload' => $payload,
            ]);

            $response = Http::timeout($this->timeout)
                ->withoutVerifying() // Disable SSL verification for development
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                    'password' => $this->password,
                    'x-signature' => $signature,
                    'X-IBM-Client-Secret' => $this->clientSecret,
                    'X-IBM-Client-Id' => $this->clientId,
                ])
                ->withBody($message, 'application/json')
                ->post($url);

            $responseData = $response->json();

            $this->log('info', 'Stanbic API response received', [
                'status' => $response->status(),
                'data' => $responseData,
            ]);

            if (!$response->successful()) {
                $this->log('error', 'API request failed', [
                    'status' => $response->status(),
                    'response' => $responseData,
                ]);
            }

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'data' => $responseData,
            ];

        } catch (Exception $e) {
            $this->log('error', 'API request exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Collect money from customer (Collection/Payment In)
     */
    public function collectMoney(
        string $phone,
        float $amount,
        string $network,
        ?string $narrative = null,
        ?string $customRequestId = null
    ): array {
        try {
            // Use custom request ID if provided (for retries), otherwise generate unique one
            $requestId = $customRequestId ?? $this->generateUniqueRequestId();
            
            $payload = [
                'msisdn' => $phone,
                'requestId' => $requestId,
                'merchantCode' => $this->merchantCode,
                'clientId' => $this->clientName,
                'referenceNumber' => (string) time(),
                'amount' => (string) $amount,
                'sourceSystem' => $network,
                'narrative' => $narrative ?? 'Payment collection',
            ];

            $this->log('info', 'Initiating money collection', [
                'phone' => $phone,
                'amount' => $amount,
                'network' => $network,
                'requestId' => $requestId,
            ]);

            $result = $this->makeRequest($this->config['endpoints']['collection'], $payload);

            return [
                'success' => $result['success'],
                'request_id' => $requestId,
                'response' => $result['data'],
                'error' => $result['error'] ?? null,
            ];

        } catch (Exception $e) {
            $this->log('error', 'Money collection failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disburse money to customer (Disbursement/Payment Out)
     */
    public function disburseMoney(
        string $phone,
        float $amount,
        string $network,
        string $beneficiaryName,
        ?string $narrative = null,
        ?string $customRequestId = null
    ): array {
        try {
            // Use custom request ID if provided (for retries), otherwise generate unique one
            $requestId = $customRequestId ?? $this->generateUniqueRequestId();
            
            $payload = [
                'requestId' => $requestId,
                'destination' => 'MNO',
                'clientId' => $this->clientName,
                'amount' => (string) $amount,
                'network' => $network,
                'narrative' => $narrative ?? 'Disbursement',
                'beneficiaryAccount' => $phone,
                'beneficiaryName' => $beneficiaryName,
                'mobileNumber' => $phone,
            ];

            $this->log('info', 'Initiating money disbursement', [
                'phone' => $phone,
                'amount' => $amount,
                'network' => $network,
                'beneficiary' => $beneficiaryName,
                'requestId' => $requestId,
            ]);

            $result = $this->makeRequest($this->config['endpoints']['disbursement'], $payload);

            return [
                'success' => $result['success'],
                'request_id' => $requestId,
                'response' => $result['data'],
                'error' => $result['error'] ?? null,
            ];

        } catch (Exception $e) {
            $this->log('error', 'Money disbursement failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check transaction status
     */
    public function checkStatus(string $requestId, string $network): array
    {
        try {
            $payload = [
                'requestId' => $requestId,
                'clientId' => $this->clientName,
                'network' => $network,
            ];

            $this->log('info', 'Checking transaction status', [
                'requestId' => $requestId,
                'network' => $network,
            ]);

            $result = $this->makeRequest($this->config['endpoints']['status_check'], $payload);

            return [
                'success' => $result['success'],
                'request_id' => $requestId,
                'response' => $result['data'],
                'error' => $result['error'] ?? null,
            ];

        } catch (Exception $e) {
            $this->log('error', 'Status check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detect network from phone number
     */
    public function detectNetwork(string $phone): ?string
    {
        // Normalize phone number
        $phone = preg_replace('/\D/', '', $phone);
        
        // Check for 256 country code
        if (substr($phone, 0, 3) === '256') {
            $phone = substr($phone, 3);
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }

        $prefix = substr($phone, 0, 2);

        foreach ($this->config['networks'] as $networkCode => $networkConfig) {
            if (in_array($prefix, $networkConfig['prefixes'])) {
                return $networkCode;
            }
        }

        return null;
    }

    /**
     * Format phone number to international format (256XXXXXXXXX)
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle different formats
        if (substr($phone, 0, 3) === '256') {
            // Already in correct format
            return $phone;
        } elseif (substr($phone, 0, 1) === '0') {
            // Local format (0772123456) - remove 0 and add 256
            return '256' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            // Format without leading 0 (772123456)
            return '256' . $phone;
        }

        // Return as is if format is unclear
        return $phone;
    }

    /**
     * Validate transaction amount
     */
    public function validateAmount(float $amount, ?string $network = null): array
    {
        $limits = $this->config['limits'];

        // Check network-specific limits
        if ($network && isset($limits[$network])) {
            $min = $limits[$network]['min'];
            $max = $limits[$network]['max'];
        } else {
            $min = $limits['min_amount'];
            $max = $limits['max_amount'];
        }

        if ($amount < $min) {
            return [
                'valid' => false,
                'message' => "Amount must be at least " . number_format($min) . " UGX",
            ];
        }

        if ($amount > $max) {
            return [
                'valid' => false,
                'message' => "Amount cannot exceed " . number_format($max) . " UGX",
            ];
        }

        return ['valid' => true];
    }

    /**
     * Test API connectivity
     */
    public function testConnection(): array
    {
        try {
            // Test OAuth token generation
            $token = $this->getAccessToken();

            return [
                'connection' => true,
                'message' => 'Successfully connected to Stanbic FlexiPay API',
                'token_generated' => !empty($token),
            ];

        } catch (Exception $e) {
            return [
                'connection' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'token_generated' => false,
            ];
        }
    }

    /**
     * Clear cached OAuth token
     */
    public function clearTokenCache(): void
    {
        if ($this->config['token_cache']['enabled']) {
            Cache::forget($this->config['token_cache']['cache_key']);
            $this->log('info', 'OAuth token cache cleared');
        }
    }

    /**
     * Generate unique request ID to prevent collisions
     * Max length: 16 characters for Stanbic API
     */
    private function generateUniqueRequestId(): string
    {
        // Format: EbP{last6-timestamp}{microsec3}{random2}
        // Example: EbP208899123456 (total: 16 chars)
        $timestamp = substr((string)time(), -6); // Last 6 digits of timestamp
        $microsec = substr(microtime(false), 2, 3); // 3 digits of microseconds
        $random = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT); // 2 random digits
        
        return $this->config['request_prefix'] . $timestamp . $microsec . $random;
    }

    /**
     * Log message with context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }

        $logMessage = '[Stanbic FlexiPay] ' . $message;

        switch ($level) {
            case 'debug':
                Log::debug($logMessage, $context);
                break;
            case 'info':
                Log::info($logMessage, $context);
                break;
            case 'warning':
                Log::warning($logMessage, $context);
                break;
            case 'error':
                Log::error($logMessage, $context);
                break;
            default:
                Log::info($logMessage, $context);
        }
    }

    /**
     * Get supported networks
     */
    public function getSupportedNetworks(): array
    {
        return $this->config['networks'];
    }
}
