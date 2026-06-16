<?php

declare(strict_types=1);

return [
    'USDC' => [
        'name'     => 'USD Coin',
        'decimals' => 6,
        'icon'     => 'usdc',
        'networks' => [
            'polygon'  => env('USDC_POLYGON', '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359'),
            'base'     => env('USDC_BASE', '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913'),
            'arbitrum' => env('USDC_ARBITRUM', '0xaf88d065e77c8cC2239327C5EDb3A432268e5831'),
            'optimism' => env('USDC_OPTIMISM', '0x0b2C639c533813f4Aa9D7837CAf62653d097Ff85'),
            'ethereum' => env('USDC_ETHEREUM', '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48'),
        ],
    ],
    'USDT' => [
        'name'     => 'Tether USD',
        'decimals' => 6,
        'icon'     => 'usdt',
        'networks' => [
            'polygon'  => env('USDT_POLYGON', '0xc2132D05D31c914a87C6611C10748AEb04B58e8F'),
            'arbitrum' => env('USDT_ARBITRUM', '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9'),
            'optimism' => env('USDT_OPTIMISM', '0x94b008aA00579c1307B0EF2c499aD98a8ce58e58'),
            'ethereum' => env('USDT_ETHEREUM', '0xdAC17F958D2ee523a2206206994597C13D831ec7'),
        ],
    ],
    'WETH' => [
        'name'     => 'Wrapped Ether',
        'decimals' => 18,
        'icon'     => 'weth',
        'networks' => [
            'polygon'  => env('WETH_POLYGON', '0x7ceB23fD6bC0adD59E62ac25578270cFf1b9f619'),
            'base'     => env('WETH_BASE', '0x4200000000000000000000000000000000000006'),
            'arbitrum' => env('WETH_ARBITRUM', '0x82aF49447D8a07e3bd95BD0d56f35241523fBab1'),
            'optimism' => env('WETH_OPTIMISM', '0x4200000000000000000000000000000000000006'),
        ],
    ],
    'WBTC' => [
        'name'     => 'Wrapped Bitcoin',
        'decimals' => 8,
        'icon'     => 'wbtc',
        'networks' => [
            'polygon'  => env('WBTC_POLYGON', '0x1BFD67037B42Cf73acF2047067bd4F2C47D9BfD6'),
            'arbitrum' => env('WBTC_ARBITRUM', '0x2f2a2543B76A4166549F7aaB2e75Bef0aefC5B0f'),
            'ethereum' => env('WBTC_ETHEREUM', '0x2260FAC5E5542a773Aa44fBCfeDf7C193bc2C599'),
        ],
    ],
];
