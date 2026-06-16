<?php

declare(strict_types=1);

return [
    'enabled'     => env('ILP_ENABLED', false),
    'ilp_address' => env('ILP_ADDRESS', 'g.finaegis'),
    'connector'   => [
        'backend'  => env('ILP_BACKEND', 'one-to-one'),
        'spread'   => (float) env('ILP_SPREAD', 0.0),
        'slippage' => (float) env('ILP_SLIPPAGE', 0.01),
    ],
    'stream' => [
        'max_packet_amount'  => (int) env('ILP_MAX_PACKET', 1000000),
        'connection_timeout' => (int) env('ILP_CONN_TIMEOUT', 30),
    ],
    'open_payments' => [
        'enabled'           => env('OPEN_PAYMENTS_ENABLED', false),
        'auth_server'       => env('OPEN_PAYMENTS_AUTH_SERVER'),
        'resource_server'   => env('OPEN_PAYMENTS_RESOURCE_SERVER'),
        'grant_ttl_seconds' => (int) env('OPEN_PAYMENTS_GRANT_TTL', 3600),
    ],
    'supported_assets' => [
        'USD' => ['code' => 'USD', 'scale' => 2],
        'EUR' => ['code' => 'EUR', 'scale' => 2],
        'GBP' => ['code' => 'GBP', 'scale' => 2],
        'BTC' => ['code' => 'BTC', 'scale' => 8],
        'ETH' => ['code' => 'ETH', 'scale' => 18],
    ],
];
