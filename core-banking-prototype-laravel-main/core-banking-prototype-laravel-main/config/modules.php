<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Module System Configuration
    |--------------------------------------------------------------------------
    |
    | Controls the FinAegis modular domain system. When all_enabled is true,
    | every domain is active. Set to false and populate the disabled list
    | to selectively disable specific domain modules.
    |
    */

    'all_enabled' => env('MODULES_ALL_ENABLED', true),

    'disabled' => array_filter(explode(',', env('MODULES_DISABLED', ''))),

];
