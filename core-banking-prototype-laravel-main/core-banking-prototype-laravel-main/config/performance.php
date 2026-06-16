<?php

declare(strict_types=1);

return [
    'query_logging'           => env('PERFORMANCE_QUERY_LOGGING', false),
    'slow_query_threshold_ms' => env('PERFORMANCE_SLOW_QUERY_MS', 100),
    'n_plus_one_detection'    => env('PERFORMANCE_N_PLUS_ONE_DETECTION', true),
    'n_plus_one_threshold'    => env('PERFORMANCE_N_PLUS_ONE_THRESHOLD', 5),
    'add_headers'             => env('PERFORMANCE_ADD_HEADERS', false),
    'hot_path_ttl'            => env('PERFORMANCE_HOT_PATH_TTL', 300),
];
