<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'flexipay' => [
        'base_url' => env('FLEXIPAY_BASE_URL', 'https://emuria.net/flexipay/'),
        'merchant_id' => env('FLEXIPAY_MERCHANT_ID'),
        'api_key' => env('FLEXIPAY_API_KEY'),
        'callback_url' => env('FLEXIPAY_CALLBACK_URL'),
        'endpoints' => [
            'disburse' => 'marchanToMobilePayprod.php',
            'collect' => 'marchanFromMobileProd.php',
            'status_check' => 'checkFromMMStatusProd.php',
        ],
        'networks' => [
            'mtn_prefixes' => ['77', '78', '76'],
            'airtel_prefixes' => ['70', '75', '74'],
        ],
    ],

    'stanbic' => [
        'client_id' => env('STANBIC_CLIENT_ID'),
        'client_secret' => env('STANBIC_CLIENT_SECRET'),
        'merchant_code' => env('STANBIC_MERCHANT_CODE'),
        'client_name' => env('STANBIC_CLIENT_NAME'),
        'password' => env('STANBIC_PASSWORD'),
        'private_key' => env('STANBIC_PRIVATE_KEY'),
        'base_url' => env('STANBIC_BASE_URL', 'https://gateway.apps.platform.stanbicbank.co.ug'),
        'timeout' => env('STANBIC_TIMEOUT', 60),
        'enabled' => env('STANBIC_ENABLED', true),
        'test_mode' => env('STANBIC_TEST_MODE', false),
        'log_level' => env('STANBIC_LOG_LEVEL', 'info'),
    ],

];
