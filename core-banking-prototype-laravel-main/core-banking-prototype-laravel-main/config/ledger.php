<?php

declare(strict_types=1);

return [
    'driver'                  => env('LEDGER_DRIVER', 'eloquent'),
    'auto_posting'            => (bool) env('LEDGER_AUTO_POSTING', true),
    'reconciliation_schedule' => env('LEDGER_RECONCILIATION_SCHEDULE', 'daily'),
    'tigerbeetle'             => [
        'addresses'  => env('TIGERBEETLE_ADDRESSES', '127.0.0.1:3001'),
        'cluster_id' => (int) env('TIGERBEETLE_CLUSTER_ID', 0),
        'timeout_ms' => (int) env('TIGERBEETLE_TIMEOUT', 5000),
    ],
    'default_currency' => env('LEDGER_DEFAULT_CURRENCY', 'USD'),
];
