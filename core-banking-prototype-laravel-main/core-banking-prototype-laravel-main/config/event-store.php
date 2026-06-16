<?php

declare(strict_types=1);

// SPDX-License-Identifier: Apache-2.0
// Copyright (c) 2024-2026 FinAegis Contributors

return [

    /*
    |--------------------------------------------------------------------------
    | Event Archival Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how old events are archived from the main stored_events table
    | to the archived_events table for long-term storage.
    |
    */
    'archival' => [
        'enabled'                => env('EVENT_STORE_ARCHIVAL_ENABLED', false),
        'default_retention_days' => (int) env('EVENT_STORE_RETENTION_DAYS', 365),
        'batch_size'             => (int) env('EVENT_STORE_ARCHIVAL_BATCH_SIZE', 1000),

        // Per-domain overrides for retention days
        'per_domain' => [
            // 'Account' => 730,
            // 'Compliance' => 2555,  // 7 years for compliance
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Compaction Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how events are compacted for aggregates. Compaction removes
    | old events that are no longer needed because a snapshot exists.
    |
    */
    'compaction' => [
        'enabled'          => env('EVENT_STORE_COMPACTION_ENABLED', false),
        'keep_latest'      => (int) env('EVENT_STORE_KEEP_LATEST', 100),
        'require_snapshot' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Partitioning Strategy
    |--------------------------------------------------------------------------
    |
    | Controls how the event store is partitioned. Currently supports:
    | - none: No partitioning (default)
    | - date: Partition by date
    | - domain: Partition by domain
    |
    */
    'partitioning' => [
        'strategy' => env('EVENT_STORE_PARTITION_STRATEGY', 'domain'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Routing Configuration
    |--------------------------------------------------------------------------
    |
    | Controls how events are routed to domain-specific tables.
    | The default_table is used for events from unmapped domains.
    | The domain_tables map overrides the built-in defaults in EventRouter.
    |
    */
    'routing' => [
        'default_table' => env('EVENT_STORE_DEFAULT_TABLE', 'stored_events'),

        // Override domain-to-table mappings (leave empty to use defaults)
        'domain_tables' => [
            // 'Account' => 'custom_account_events',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Thresholds for event store health checks. These control when health
    | checks report warnings or unhealthy status.
    |
    */
    'health' => [
        'growth_rate_threshold' => (int) env('EVENT_STORE_GROWTH_RATE_THRESHOLD', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Versioning Configuration
    |--------------------------------------------------------------------------
    |
    | Controls event schema versioning and upcasting behavior.
    | When enabled, events are automatically upcasted during replay.
    |
    */
    'versioning' => [
        'enabled'          => env('EVENT_STORE_VERSIONING_ENABLED', true),
        'upcast_on_replay' => env('EVENT_STORE_UPCAST_ON_REPLAY', true),
        'default_version'  => 1,
    ],

];
