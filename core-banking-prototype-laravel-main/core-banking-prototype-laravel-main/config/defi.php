<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | DeFi Protocol Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for DeFi protocol integrations including Uniswap, Aave,
    | Curve, and Lido connectors.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Swap Provider
    |--------------------------------------------------------------------------
    */

    'default_swap_provider' => env('DEFI_DEFAULT_SWAP', 'demo'),

    /*
    |--------------------------------------------------------------------------
    | Swap Defaults
    |--------------------------------------------------------------------------
    */

    'swap' => [
        'default_slippage'  => env('DEFI_DEFAULT_SLIPPAGE', 0.5),
        'max_slippage'      => env('DEFI_MAX_SLIPPAGE', 5.0),
        'quote_ttl_seconds' => env('DEFI_QUOTE_TTL', 60),
        'max_price_impact'  => env('DEFI_MAX_PRICE_IMPACT', 3.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uniswap V3 Configuration
    |--------------------------------------------------------------------------
    */

    'uniswap' => [
        'enabled' => env('UNISWAP_ENABLED', false),
        'routers' => [
            'ethereum' => env('UNISWAP_ROUTER_ETH', '0xE592427A0AEce92De3Edee1F18E0157C05861564'),
            'polygon'  => env('UNISWAP_ROUTER_POLYGON', '0xE592427A0AEce92De3Edee1F18E0157C05861564'),
            'arbitrum' => env('UNISWAP_ROUTER_ARBITRUM', '0xE592427A0AEce92De3Edee1F18E0157C05861564'),
            'optimism' => env('UNISWAP_ROUTER_OPTIMISM', '0xE592427A0AEce92De3Edee1F18E0157C05861564'),
            'base'     => env('UNISWAP_ROUTER_BASE', '0x2626664c2603336E57B271c5C0b26F421741e481'),
        ],
        'quoters' => [
            'ethereum' => env('UNISWAP_QUOTER_ETH', '0xb27308f9F90D607463bb33eA1BeBb41C27CE5AB6'),
            'polygon'  => env('UNISWAP_QUOTER_POLYGON', '0xb27308f9F90D607463bb33eA1BeBb41C27CE5AB6'),
            'arbitrum' => env('UNISWAP_QUOTER_ARBITRUM', '0xb27308f9F90D607463bb33eA1BeBb41C27CE5AB6'),
            'optimism' => env('UNISWAP_QUOTER_OPTIMISM', '0xb27308f9F90D607463bb33eA1BeBb41C27CE5AB6'),
            'base'     => env('UNISWAP_QUOTER_BASE', '0x3d4e44Eb1374240CE5F1B871ab261CD16335B76a'),
        ],
        'fee_tiers' => [100, 500, 3000, 10000],
    ],

    /*
    |--------------------------------------------------------------------------
    | Aave V3 Configuration
    |--------------------------------------------------------------------------
    */

    'aave' => [
        'enabled' => env('AAVE_ENABLED', false),
        'pools'   => [
            'ethereum' => env('AAVE_POOL_ETH', '0x87870Bca3F3fD6335C3F4ce8392D69350B4fA4E2'),
            'polygon'  => env('AAVE_POOL_POLYGON', '0x794a61358D6845594F94dc1DB02A252b5b4814aD'),
            'arbitrum' => env('AAVE_POOL_ARBITRUM', '0x794a61358D6845594F94dc1DB02A252b5b4814aD'),
            'optimism' => env('AAVE_POOL_OPTIMISM', '0x794a61358D6845594F94dc1DB02A252b5b4814aD'),
            'base'     => env('AAVE_POOL_BASE', '0xA238Dd80C259a72e81d7e4664a9801593F98d1c5'),
        ],
        'flash_loan_fee' => env('AAVE_FLASH_LOAN_FEE', 0.0005),
    ],

    /*
    |--------------------------------------------------------------------------
    | Curve Finance Configuration
    |--------------------------------------------------------------------------
    */

    'curve' => [
        'enabled'  => env('CURVE_ENABLED', false),
        'registry' => [
            'ethereum' => env('CURVE_REGISTRY_ETH', '0x90E00ACe148ca3b23Ac1bC8C240C2a7Dd9c2d7f5'),
            'polygon'  => env('CURVE_REGISTRY_POLYGON', '0x0000000022D53366457F9d5E68Ec105046FC4383'),
            'arbitrum' => env('CURVE_REGISTRY_ARBITRUM', '0x0000000022D53366457F9d5E68Ec105046FC4383'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lido Configuration
    |--------------------------------------------------------------------------
    */

    'lido' => [
        'enabled'          => env('LIDO_ENABLED', false),
        'steth'            => env('LIDO_STETH', '0xae7ab96520DE3A18E5e111B5EaAb095312D7fE84'),
        'wsteth'           => env('LIDO_WSTETH', '0x7f39C581F595B53c5cb19bD0b3f8dA6c935E2Ca0'),
        'withdrawal_queue' => env('LIDO_WITHDRAWAL', '0x889edC2eDab5f40e902b864aD4d7AdE8E412F9B1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Configuration
    |--------------------------------------------------------------------------
    */

    'demo' => [
        'simulated_swap_fee'    => env('DEFI_DEMO_SWAP_FEE', 0.003),
        'simulated_supply_apy'  => env('DEFI_DEMO_SUPPLY_APY', 3.5),
        'simulated_borrow_apy'  => env('DEFI_DEMO_BORROW_APY', 5.2),
        'simulated_staking_apy' => env('DEFI_DEMO_STAKING_APY', 3.8),
    ],
];
