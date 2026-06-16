<?php

declare(strict_types=1);

return [
    'enabled'                    => env('OPEN_BANKING_ENABLED', false),
    'standard'                   => env('OPEN_BANKING_STANDARD', 'berlin_group'),
    'consent_max_days'           => (int) env('OPEN_BANKING_CONSENT_MAX_DAYS', 90),
    'frequency_per_day'          => (int) env('OPEN_BANKING_FREQUENCY', 4),
    'require_sca'                => (bool) env('OPEN_BANKING_REQUIRE_SCA', true),
    'tpp_certificate_validation' => (bool) env('OPEN_BANKING_VALIDATE_CERTS', true),
    'supported_permissions'      => [
        'ReadAccountsBasic',
        'ReadAccountsDetail',
        'ReadBalances',
        'ReadTransactionsBasic',
        'ReadTransactionsDetail',
        'ReadTransactionsCredits',
        'ReadTransactionsDebits',
    ],
];
