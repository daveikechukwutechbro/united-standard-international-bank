<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Enums;

enum OpenBankingStandard: string
{
    case BERLIN_GROUP = 'berlin_group';
    case UK_OB = 'uk_ob';

    public function label(): string
    {
        return match ($this) {
            self::BERLIN_GROUP => 'Berlin Group NextGenPSD2',
            self::UK_OB        => 'UK Open Banking',
        };
    }
}
