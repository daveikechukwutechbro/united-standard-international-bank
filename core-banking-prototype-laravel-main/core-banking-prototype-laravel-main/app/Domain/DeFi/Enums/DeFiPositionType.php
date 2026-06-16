<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Enums;

enum DeFiPositionType: string
{
    case SWAP = 'swap';
    case SUPPLY = 'supply';
    case BORROW = 'borrow';
    case LP = 'lp';
    case STAKE = 'stake';
    case YIELD_VAULT = 'yield_vault';
}
