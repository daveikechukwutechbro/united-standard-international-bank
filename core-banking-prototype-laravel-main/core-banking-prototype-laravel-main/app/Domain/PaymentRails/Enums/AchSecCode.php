<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Enums;

enum AchSecCode: string
{
    case PPD = 'PPD';
    case CCD = 'CCD';
    case WEB = 'WEB';
    case TEL = 'TEL';
    case CTX = 'CTX';

    public function label(): string
    {
        return match ($this) {
            self::PPD => 'Prearranged Payment/Deposit',
            self::CCD => 'Corporate Credit/Debit',
            self::WEB => 'Internet',
            self::TEL => 'Telephone',
            self::CTX => 'Corporate Trade Exchange',
        };
    }
}
