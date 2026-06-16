<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Enums;

enum MemberRole: string
{
    case LEADER = 'leader';
    case SECRETARY = 'secretary';
    case TREASURER = 'treasurer';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::LEADER    => 'Leader',
            self::SECRETARY => 'Secretary',
            self::TREASURER => 'Treasurer',
            self::MEMBER    => 'Member',
        };
    }
}
