<?php

declare(strict_types=1);

namespace App\Domain\ISO8583\Enums;

enum ResponseCode: string
{
    case APPROVED = '00';
    case DECLINED = '05';
    case INSUFFICIENT_FUNDS = '51';
    case EXPIRED_CARD = '54';
    case INVALID_TRANSACTION = '12';
    case DO_NOT_HONOR = '57';
    case SYSTEM_ERROR = '96';

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function label(): string
    {
        return match ($this) {
            self::APPROVED            => 'Approved',
            self::DECLINED            => 'Do Not Honor',
            self::INSUFFICIENT_FUNDS  => 'Insufficient Funds',
            self::EXPIRED_CARD        => 'Expired Card',
            self::INVALID_TRANSACTION => 'Invalid Transaction',
            self::DO_NOT_HONOR        => 'Do Not Honor',
            self::SYSTEM_ERROR        => 'System Error',
        };
    }
}
