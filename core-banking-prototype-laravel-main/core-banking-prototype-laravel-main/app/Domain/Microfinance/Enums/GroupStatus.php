<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Enums;

enum GroupStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE  => 'Active',
            self::CLOSED  => 'Closed',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
