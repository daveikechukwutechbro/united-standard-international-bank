<?php

declare(strict_types=1);

return [
    'ach' => [
        'enabled'          => env('ACH_ENABLED', false),
        'originator_id'    => env('ACH_ORIGINATOR_ID'),
        'originating_dfi'  => env('ACH_ORIGINATING_DFI'),
        'company_name'     => env('ACH_COMPANY_NAME', 'FinAegis'),
        'company_id'       => env('ACH_COMPANY_ID'),
        'same_day_enabled' => (bool) env('ACH_SAME_DAY', true),
        'cutoff_time'      => env('ACH_CUTOFF_TIME', '16:30'),
        'cutoff_timezone'  => env('ACH_CUTOFF_TIMEZONE', 'America/New_York'),
    ],
    'fedwire' => [
        'enabled'    => env('FEDWIRE_ENABLED', false),
        'sender_aba' => env('FEDWIRE_SENDER_ABA'),
        'endpoint'   => env('FEDWIRE_ENDPOINT'),
        'timeout'    => (int) env('FEDWIRE_TIMEOUT', 30),
    ],
    'rtp' => [
        'enabled'        => env('RTP_ENABLED', false),
        'participant_id' => env('RTP_PARTICIPANT_ID'),
        'endpoint'       => env('RTP_ENDPOINT'),
        'max_amount'     => (int) env('RTP_MAX_AMOUNT', 100000000),
    ],
    'fednow' => [
        'enabled'        => env('FEDNOW_ENABLED', false),
        'participant_id' => env('FEDNOW_PARTICIPANT_ID'),
        'endpoint'       => env('FEDNOW_ENDPOINT'),
        'max_amount'     => (int) env('FEDNOW_MAX_AMOUNT', 50000000),
    ],
];
