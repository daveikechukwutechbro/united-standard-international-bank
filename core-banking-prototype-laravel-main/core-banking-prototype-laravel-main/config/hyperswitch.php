<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | HyperSwitch Payment Orchestration Configuration
    |--------------------------------------------------------------------------
    |
    | HyperSwitch by Juspay is an open-source payment switch that provides
    | unified multi-processor routing, failover, and smart payment orchestration.
    | See: https://github.com/juspay/hyperswitch
    |
    */

    'enabled' => (bool) env('HYPERSWITCH_ENABLED', false),

    // API base URL — sandbox for dev, self-hosted or cloud for production
    'base_url' => env('HYPERSWITCH_BASE_URL', 'https://sandbox.hyperswitch.io'),

    // Server-side API key (secret)
    'api_key' => env('HYPERSWITCH_API_KEY', ''),

    // Publishable key (client-side)
    'publishable_key' => env('HYPERSWITCH_PUBLISHABLE_KEY', ''),

    // Webhook signing secret for verifying incoming webhooks
    'webhook_secret' => env('HYPERSWITCH_WEBHOOK_SECRET', ''),

    // Default business profile ID
    'profile_id' => env('HYPERSWITCH_PROFILE_ID', ''),

    // Timeout for API calls in seconds
    'timeout' => (int) env('HYPERSWITCH_TIMEOUT', 30),

    // Default settings
    'defaults' => [
        'currency'            => env('HYPERSWITCH_DEFAULT_CURRENCY', 'EUR'),
        'capture_method'      => 'automatic', // automatic | manual
        'authentication_type' => 'three_ds',  // three_ds | no_three_ds
        'return_url'          => env('HYPERSWITCH_RETURN_URL', ''),
    ],

    // Routing configuration
    'routing' => [
        'strategy' => env('HYPERSWITCH_ROUTING_STRATEGY', 'priority'), // priority | volume_split | advanced
    ],
];
