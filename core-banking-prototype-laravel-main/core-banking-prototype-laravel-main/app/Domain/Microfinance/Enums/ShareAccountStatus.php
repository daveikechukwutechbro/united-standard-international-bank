<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Enums;

enum ShareAccountStatus: string
{
    case ACTIVE = 'active';
    case DORMANT = 'dormant';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE  => 'Active',
            self::DORMANT => 'Dormant',
            self::CLOSED  => 'Closed',
        };
    }
}
