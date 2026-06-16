<?php

declare(strict_types=1);

namespace App\Domain\X402\Enums;

/**
 * x402 payment scheme types.
 *
 * Defines how the payment amount is interpreted:
 * - EXACT: The payer must transfer the exact specified amount.
 * - UPTO: The payer may transfer up to the specified amount.
 */
enum PaymentScheme: string
{
    case EXACT = 'exact';
    case UPTO = 'upto';

    public function label(): string
    {
        return match ($this) {
            self::EXACT => 'Exact Amount',
            self::UPTO  => 'Up To Amount',
        };
    }
}
