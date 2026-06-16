<?php

declare(strict_types=1);

return [
    'enabled'                     => env('MFI_ENABLED', false),
    'max_group_size'              => (int) env('MFI_MAX_GROUP_SIZE', 30),
    'min_group_size'              => (int) env('MFI_MIN_GROUP_SIZE', 5),
    'meeting_attendance_required' => (bool) env('MFI_ATTENDANCE_REQUIRED', true),
    'provisioning'                => [
        'standard_days'    => (int) env('MFI_PROVISION_STANDARD', 30),
        'substandard_days' => (int) env('MFI_PROVISION_SUBSTANDARD', 90),
        'doubtful_days'    => (int) env('MFI_PROVISION_DOUBTFUL', 180),
        'loss_days'        => (int) env('MFI_PROVISION_LOSS', 365),
        'standard_rate'    => (float) env('MFI_PROVISION_STANDARD_RATE', 0.01),
        'substandard_rate' => (float) env('MFI_PROVISION_SUBSTANDARD_RATE', 0.05),
        'doubtful_rate'    => (float) env('MFI_PROVISION_DOUBTFUL_RATE', 0.50),
        'loss_rate'        => (float) env('MFI_PROVISION_LOSS_RATE', 1.00),
    ],
    'share_accounts' => [
        'nominal_value' => (float) env('MFI_SHARE_NOMINAL', 100.00),
        'min_shares'    => (int) env('MFI_MIN_SHARES', 1),
        'max_shares'    => (int) env('MFI_MAX_SHARES', 1000),
    ],
    'dormancy' => [
        'days_until_dormant' => (int) env('MFI_DORMANCY_DAYS', 180),
        'days_until_escheat' => (int) env('MFI_ESCHEAT_DAYS', 1095),
    ],
];
