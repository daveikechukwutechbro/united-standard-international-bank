<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Networks
    |--------------------------------------------------------------------------
    |
    | Networks supported for mobile payments. v1: Solana + Tron only.
    |
    */
    'allowed_networks' => explode(',', env('MOBILE_PAYMENT_NETWORKS', 'SOLANA,TRON')),

    /*
    |--------------------------------------------------------------------------
    | Allowed Assets
    |--------------------------------------------------------------------------
    |
    | Assets supported for mobile payments. v1: USDC only.
    |
    */
    'allowed_assets' => explode(',', env('MOBILE_PAYMENT_ASSETS', 'USDC')),

    /*
    |--------------------------------------------------------------------------
    | Payment Intent Expiry
    |--------------------------------------------------------------------------
    |
    | How many minutes before an unsubmitted payment intent expires.
    |
    */
    'expiry_minutes' => (int) env('MOBILE_PAYMENT_EXPIRY_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Receipt Cache TTL
    |--------------------------------------------------------------------------
    |
    | How many hours to cache generated receipts in Redis.
    |
    */
    'receipt_cache_hours' => (int) env('MOBILE_PAYMENT_RECEIPT_CACHE_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Idempotency Queue Window
    |--------------------------------------------------------------------------
    |
    | Maximum age (in minutes) for accepting queued/retried submissions
    | from mobile clients that were offline. Aligns with offline queue TTL.
    |
    */
    'idempotency_queue_window_minutes' => (int) env('MOBILE_PAYMENT_IDEMPOTENCY_QUEUE_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | Use demo/stub implementations for blockchain interactions.
    |
    */
    'demo_mode' => (bool) env('MOBILE_PAYMENT_DEMO_MODE', true),
];
