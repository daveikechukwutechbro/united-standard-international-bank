<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Enums;

enum DeFiPositionStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case LIQUIDATED = 'liquidated';

    public function isOpen(): bool
    {
        return $this === self::ACTIVE;
    }
}
