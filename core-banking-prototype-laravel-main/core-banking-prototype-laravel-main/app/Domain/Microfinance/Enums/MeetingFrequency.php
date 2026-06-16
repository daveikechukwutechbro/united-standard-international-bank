<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Enums;

enum MeetingFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY    => 'Daily',
            self::WEEKLY   => 'Weekly',
            self::BIWEEKLY => 'Bi-Weekly',
            self::MONTHLY  => 'Monthly',
        };
    }
}
