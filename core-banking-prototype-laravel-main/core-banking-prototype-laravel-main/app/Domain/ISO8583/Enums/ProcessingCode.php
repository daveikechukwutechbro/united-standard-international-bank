<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Enums;

enum ProcessingCode: string
{
    case PURCHASE = '00';
    case CASH_ADVANCE = '01';
    case REFUND = '20';
    case BALANCE_INQUIRY = '31';

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE        => 'Purchase',
            self::CASH_ADVANCE    => 'Cash Advance',
            self::REFUND          => 'Refund',
            self::BALANCE_INQUIRY => 'Balance Inquiry',
        };
    }
}
