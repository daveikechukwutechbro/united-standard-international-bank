<?php

declare(strict_types=1);

namespace App\Domain\Relayer\Enums;

/**
 * Status of a sponsored transaction.
 */
enum TransactionStatus: string
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function isFinal(): bool
    {
        return in_array($this, [self::CONFIRMED, self::FAILED, self::REFUNDED]);
    }
}
