<?php

declare(strict_types=1);

return [
    'enabled'       => env('ISO8583_ENABLED', false),
    'version'       => env('ISO8583_VERSION', '1993'),
    'header_length' => (int) env('ISO8583_HEADER_LENGTH', 2),
    'encoding'      => env('ISO8583_ENCODING', 'ascii'),
    'networks'      => [
        'visa' => [
            'host'       => env('VISA_ISO8583_HOST'),
            'port'       => (int) env('VISA_ISO8583_PORT', 9100),
            'timeout'    => (int) env('VISA_ISO8583_TIMEOUT', 30),
            'bin_ranges' => ['4'],
        ],
        'mastercard' => [
            'host'       => env('MC_ISO8583_HOST'),
            'port'       => (int) env('MC_ISO8583_PORT', 9200),
            'timeout'    => (int) env('MC_ISO8583_TIMEOUT', 30),
            'bin_ranges' => ['5', '2'],
        ],
    ],
];
