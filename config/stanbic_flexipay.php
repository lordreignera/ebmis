<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stanbic Bank FlexiPay Mobile Money Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Stanbic Bank FlexiPay API integration
    | Supports MTN and Airtel mobile money in Uganda
    |
    */

    // API Credentials
    'client_id' => env('STANBIC_CLIENT_ID', ''),
    'client_secret' => env('STANBIC_CLIENT_SECRET', ''),
    'merchant_code' => env('STANBIC_MERCHANT_CODE', ''),
    'client_name' => env('STANBIC_CLIENT_NAME', ''),
    'password' => env('STANBIC_PASSWORD', ''),
    'private_key' => env('STANBIC_PRIVATE_KEY', ''),

    // API Endpoints
    'base_url' => env('STANBIC_BASE_URL', 'https://gateway.apps.platform.stanbicbank.co.ug'),
    
    'endpoints' => [
        'oauth_token' => '/ug/oauth2/token',
        'collection' => '/fp/v1.1/merchantpayment',
        'disbursement' => '/fp-domestic/v1.0/mobiletransfer',
        'status_check' => '/fp/v1.1/merchantpaymentstatus',
    ],

    // SSL Certificates (optional, for production)
    'ssl' => [
        'verify_host' => env('STANBIC_SSL_VERIFY_HOST', false),
        'verify_peer' => env('STANBIC_SSL_VERIFY_PEER', false),
        'cert_path' => env('STANBIC_CERT_PATH', ''),
    ],

    // API Settings
    'timeout' => env('STANBIC_TIMEOUT', 60),
    'enabled' => env('STANBIC_ENABLED', true),
    'test_mode' => env('STANBIC_TEST_MODE', false),

    // OAuth Token Caching
    'token_cache' => [
        'enabled' => true,
        'ttl' => 900, // Cache token for 15 minutes (API tokens expire ~24 minutes, safe margin)
        'cache_key' => 'stanbic_oauth_token',
    ],

    // Mobile Money Networks
    'networks' => [
        'MTN' => [
            'code' => 'MTN',
            'name' => 'MTN Uganda',
            'prefixes' => ['77', '78', '76'],
            'enabled' => true,
        ],
        'AIRTEL' => [
            'code' => 'AIRTEL',
            'name' => 'Airtel Uganda',
            'prefixes' => ['70', '75', '74'],
            'enabled' => true,
        ],
    ],

    // Transaction Limits (UGX)
    'limits' => [
        'min_amount' => 500, // 500 UGX (minimum for upfront fees)
        'max_amount' => 4000000, // 4,000,000 UGX
        'MTN' => [
            'min' => 500, // Reduced to support small upfront fees
            'max' => 4000000,
        ],
        'AIRTEL' => [
            'min' => 500, // Reduced to support small upfront fees
            'max' => 1000000,
        ],
    ],

    // Request ID Prefix
    'request_prefix' => env('STANBIC_REQUEST_PREFIX', 'EbP'),

    // Retry Configuration
    'retry' => [
        'max_attempts' => 3,
        'delay' => 5, // seconds
        'backoff_multiplier' => 2,
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => env('STANBIC_LOG_LEVEL', 'info'),
        'channel' => 'stanbic_mobile_money',
    ],

    // Error Messages
    'messages' => [
        'invalid_network' => 'Invalid mobile money network detected.',
        'amount_too_low' => 'Amount is below minimum transaction limit.',
        'amount_too_high' => 'Amount exceeds maximum transaction limit.',
        'invalid_phone' => 'Invalid phone number format.',
        'api_error' => 'Mobile money service temporarily unavailable.',
        'timeout' => 'Transaction timeout. Please try again.',
        'insufficient_balance' => 'Insufficient balance in mobile money account.',
        'transaction_failed' => 'Transaction failed. Please try again.',
        'oauth_failed' => 'Authentication failed. Please contact support.',
        'signature_failed' => 'Request signature generation failed.',
    ],

    // Status Codes Mapping
    'status_codes' => [
        '00' => 'success',
        '01' => 'pending',
        '02' => 'failed',
        '03' => 'timeout',
        '04' => 'cancelled',
    ],
];
