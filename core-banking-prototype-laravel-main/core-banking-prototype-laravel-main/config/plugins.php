<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin Directory
    |--------------------------------------------------------------------------
    |
    | The base directory where plugins are installed.
    |
    */
    'directory' => base_path('plugins'),

    /*
    |--------------------------------------------------------------------------
    | Plugin Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, plugins are automatically discovered and loaded from the
    | plugin directory on application boot.
    |
    */
    'auto_discover' => env('PLUGIN_AUTO_DISCOVER', true),

    /*
    |--------------------------------------------------------------------------
    | Plugin Sandboxing
    |--------------------------------------------------------------------------
    |
    | Controls plugin permission enforcement. When strict mode is enabled,
    | plugins can only access resources they have explicitly declared.
    |
    */
    'sandbox' => [
        'enabled'     => env('PLUGIN_SANDBOX_ENABLED', true),
        'strict_mode' => env('PLUGIN_SANDBOX_STRICT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Marketplace
    |--------------------------------------------------------------------------
    |
    | Configuration for the plugin marketplace API.
    |
    */
    'marketplace' => [
        'enabled'      => env('PLUGIN_MARKETPLACE_ENABLED', true),
        'registry_url' => env('PLUGIN_REGISTRY_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Permission Categories
    |--------------------------------------------------------------------------
    |
    | The permission categories that plugins can request.
    |
    */
    'permissions' => [
        'database:read',
        'database:write',
        'api:internal',
        'api:external',
        'events:listen',
        'events:dispatch',
        'queue:dispatch',
        'cache:read',
        'cache:write',
        'filesystem:read',
        'filesystem:write',
        'config:read',
    ],
];
