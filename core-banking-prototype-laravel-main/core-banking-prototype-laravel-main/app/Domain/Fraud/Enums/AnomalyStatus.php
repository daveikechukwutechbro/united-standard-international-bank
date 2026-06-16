<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Enums;

enum AnomalyStatus: string
{
    case Detected = 'detected';
    case Investigating = 'investigating';
    case Confirmed = 'confirmed';
    case FalsePositive = 'false_positive';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Detected      => 'Detected',
            self::Investigating => 'Under Investigation',
            self::Confirmed     => 'Confirmed',
            self::FalsePositive => 'False Positive',
            self::Resolved      => 'Resolved',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FalsePositive, self::Resolved]);
    }
}
