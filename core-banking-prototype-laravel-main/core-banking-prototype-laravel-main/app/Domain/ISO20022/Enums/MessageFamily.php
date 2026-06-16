<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\Enums;

enum MessageFamily: string
{
    case PAIN = 'pain';
    case PACS = 'pacs';
    case CAMT = 'camt';
    case ACMT = 'acmt';
    case SEMT = 'semt';

    public function label(): string
    {
        return match ($this) {
            self::PAIN => 'Payment Initiation',
            self::PACS => 'Payments Clearing and Settlement',
            self::CAMT => 'Cash Management',
            self::ACMT => 'Account Management',
            self::SEMT => 'Securities Management',
        };
    }
}
