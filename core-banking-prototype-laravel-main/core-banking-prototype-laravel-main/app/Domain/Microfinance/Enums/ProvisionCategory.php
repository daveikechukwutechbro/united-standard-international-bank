<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Enums;

enum ProvisionCategory: string
{
    case STANDARD = 'standard';
    case SUBSTANDARD = 'substandard';
    case DOUBTFUL = 'doubtful';
    case LOSS = 'loss';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD    => 'Standard',
            self::SUBSTANDARD => 'Substandard',
            self::DOUBTFUL    => 'Doubtful',
            self::LOSS        => 'Loss',
        };
    }

    public function rate(): float
    {
        return match ($this) {
            self::STANDARD    => (float) config('microfinance.provisioning.standard_rate', 0.01),
            self::SUBSTANDARD => (float) config('microfinance.provisioning.substandard_rate', 0.05),
            self::DOUBTFUL    => (float) config('microfinance.provisioning.doubtful_rate', 0.50),
            self::LOSS        => (float) config('microfinance.provisioning.loss_rate', 1.00),
        };
    }

    public function minDaysOverdue(): int
    {
        return match ($this) {
            self::STANDARD    => (int) config('microfinance.provisioning.standard_days', 30),
            self::SUBSTANDARD => (int) config('microfinance.provisioning.substandard_days', 90),
            self::DOUBTFUL    => (int) config('microfinance.provisioning.doubtful_days', 180),
            self::LOSS        => (int) config('microfinance.provisioning.loss_days', 365),
        };
    }
}
