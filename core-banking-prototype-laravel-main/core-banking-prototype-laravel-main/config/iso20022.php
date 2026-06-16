<?php

declare(strict_types=1);

return [
    'enabled'               => env('ISO20022_ENABLED', false),
    'enabled_families'      => array_filter(explode(',', (string) env('ISO20022_FAMILIES', 'pain,pacs,camt'))),
    'validation_strictness' => env('ISO20022_STRICT', true),
    'schema_path'           => storage_path('app/iso20022/schemas'),
    'default_currency'      => env('ISO20022_DEFAULT_CURRENCY', 'EUR'),
    'uetr_enabled'          => (bool) env('ISO20022_UETR_ENABLED', true),
    'max_message_size_kb'   => (int) env('ISO20022_MAX_SIZE_KB', 512),
    'supported_versions'    => [
        'pain.001' => '001.001.09',
        'pain.008' => '008.001.08',
        'pacs.008' => '008.001.08',
        'pacs.002' => '002.001.10',
        'pacs.003' => '003.001.08',
        'pacs.004' => '004.001.09',
        'camt.053' => '053.001.08',
        'camt.054' => '054.001.08',
    ],
];
