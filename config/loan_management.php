<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Loan Management Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the loan management system
    |
    */

    // Auto Approval Settings
    'auto_approve' => [
        'enabled' => false,
        'threshold' => env('LOAN_AUTO_APPROVE_THRESHOLD', 0),
        'max_amount' => 500000, // 500K UGX
        'required_savings_percentage' => 20, // 20% of loan amount
    ],

    // Disbursement Settings
    'disbursement' => [
        'max_amount' => env('LOAN_MAX_DISBURSEMENT_AMOUNT', 50000000), // 50M UGX
        'methods' => [
            'cash' => ['enabled' => true, 'code' => 0],
            'mobile_money' => ['enabled' => true, 'code' => 1],
            'bank_transfer' => ['enabled' => true, 'code' => 2],
        ],
        'default_method' => 'mobile_money',
        'require_approval' => true,
    ],

    // Repayment Settings
    'repayment' => [
        'grace_period' => env('LOAN_DEFAULT_GRACE_PERIOD', 7), // days
        'late_fee' => [
            'enabled' => true,
            'percentage' => env('LOAN_LATE_FEE_PERCENTAGE', 2.5), // 2.5%
            'max_percentage' => 10, // 10% of payment amount
            'waive_threshold' => 1, // waive if payment is 1 day late or less
        ],
        'auto_waive' => [
            'enabled' => true,
            'conditions' => [
                'full_payment' => true,
                'member_in_good_standing' => true,
            ],
        ],
        'methods' => [
            'cash' => ['enabled' => true],
            'mobile_money' => ['enabled' => true],
            'bank_transfer' => ['enabled' => true],
            'check' => ['enabled' => true],
        ],
    ],

    // Schedule Settings
    'schedule' => [
        'types' => [
            'equal_installments' => 'Equal Installments',
            'reducing_balance' => 'Reducing Balance',
            'flat_rate' => 'Flat Rate',
        ],
        'default_type' => 'equal_installments',
        'frequencies' => [
            'daily' => ['enabled' => true, 'days' => 1],
            'weekly' => ['enabled' => true, 'days' => 7],
            'bi_weekly' => ['enabled' => true, 'days' => 14],
            'monthly' => ['enabled' => true, 'days' => 30],
        ],
        'default_frequency' => 'monthly',
        'allow_regeneration' => true,
    ],

    // Fee Settings
    'fees' => [
        'types' => [
            'processing_fee' => 'Processing Fee',
            'service_charge' => 'Service Charge',
            'insurance' => 'Insurance',
            'administration_fee' => 'Administration Fee',
            'late_payment_fee' => 'Late Payment Fee',
        ],
        'charge_types' => [
            'deducted' => 'Deducted from Principal',
            'upfront' => 'Paid Upfront',
        ],
        'amount_types' => [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
        ],
        'auto_create_fee_types' => true,
    ],

    // Approval Workflow
    'approval' => [
        'required_checks' => [
            'member_exists' => true,
            'savings_requirement' => true,
            'existing_loan_check' => true,
            'blacklist_check' => true,
        ],
        'savings_requirement' => [
            'enabled' => true,
            'percentage' => 20, // 20% of loan amount
            'minimum_months' => 3, // minimum 3 months of savings
        ],
        'notification' => [
            'enabled' => true,
            'methods' => ['email', 'sms'],
        ],
    ],

    // Business Rules
    'business_rules' => [
        'max_loans_per_member' => 3,
        'max_outstanding_amount' => 10000000, // 10M UGX per member
        'minimum_loan_amount' => 50000, // 50K UGX
        'maximum_loan_amount' => 50000000, // 50M UGX
        'interest_rate' => [
            'minimum' => 5, // 5%
            'maximum' => 36, // 36% annual
        ],
        'loan_period' => [
            'minimum_days' => 30,
            'maximum_days' => 1825, // 5 years
        ],
    ],

    // System Settings
    'system' => [
        'currency' => 'UGX',
        'currency_symbol' => 'UGX',
        'decimal_places' => 2,
        'date_format' => 'd-M-Y',
        'datetime_format' => 'd-M-Y H:i',
        'pagination_per_page' => 25,
        'enable_audit_trail' => true,
    ],

    // Integration Settings
    'integrations' => [
        'mobile_money' => [
            'enabled' => env('MOBILE_MONEY_ENABLED', true),
            'primary_provider' => 'flexipay',
            'fallback_enabled' => false,
        ],
        'sms' => [
            'enabled' => true,
            'provider' => 'default',
            'notifications' => [
                'loan_approved' => true,
                'loan_rejected' => true,
                'disbursement_completed' => true,
                'payment_received' => true,
                'payment_overdue' => true,
            ],
        ],
        'email' => [
            'enabled' => true,
            'notifications' => [
                'loan_approved' => true,
                'loan_rejected' => true,
                'disbursement_completed' => true,
                'payment_reminder' => true,
            ],
        ],
    ],

    // Security Settings
    'security' => [
        'require_two_factor' => false,
        'session_timeout' => 3600, // 1 hour
        'max_failed_attempts' => 3,
        'lockout_duration' => 900, // 15 minutes
        'audit_sensitive_actions' => true,
    ],

    // Performance Settings
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 3600, // 1 hour
        'queue_jobs' => [
            'sms_notifications' => true,
            'email_notifications' => true,
            'report_generation' => true,
        ],
    ],
];