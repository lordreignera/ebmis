<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FlexiPay Mobile Money Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for FlexiPay mobile money API integration
    |
    */

    // API Configuration
    'api_url' => env('FLEXIPAY_API_URL', 'https://emuria.net/flexipay/marchanToMobilePayprod.php'),
    'merchant_code' => env('FLEXIPAY_MERCHANT_CODE', ''),
    'secret_key' => env('FLEXIPAY_SECRET_KEY', ''),
    'callback_url' => env('FLEXIPAY_CALLBACK_URL', ''),
    
    // API Settings
    'timeout' => env('FLEXIPAY_TIMEOUT', 30),
    'enabled' => env('FLEXIPAY_ENABLED', true),
    
    // Mobile Money Networks
    'networks' => [
        'mtn' => [
            'code' => 2,
            'name' => 'MTN',
            'prefixes' => ['77', '78', '76'],
            'enabled' => true,
        ],
        'airtel' => [
            'code' => 1,
            'name' => 'AIRTEL',
            'prefixes' => ['75', '70', '74'],
            'enabled' => true,
        ],
    ],
    
    // Default Settings
    'default_network' => env('MOBILE_MONEY_DEFAULT_NETWORK', 'auto'),
    'test_mode' => env('MOBILE_MONEY_TEST_MODE', true),
    
    // Transaction Limits
    'limits' => [
        'min_amount' => 1000, // 1,000 UGX
        'max_amount' => 4000000, // 4,000,000 UGX
        'mtn' => [
            'min' => 1000,
            'max' => 4000000,
        ],
        'airtel' => [
            'min' => 1000,
            'max' => 1000000, // 1,000,000 UGX for Airtel
        ],
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
        'duplicate_transaction' => 'Duplicate transaction detected.',
    ],
    
    // Status Codes
    'status_codes' => [
        'pending' => 0,
        'success' => 1,
        'failed' => 2,
        'timeout' => 3,
        'cancelled' => 4,
    ],
    
    // Retry Configuration
    'retry' => [
        'max_attempts' => 3,
        'delay' => 5, // seconds
        'backoff_multiplier' => 2,
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channels' => ['mobile_money'],
    ],
];