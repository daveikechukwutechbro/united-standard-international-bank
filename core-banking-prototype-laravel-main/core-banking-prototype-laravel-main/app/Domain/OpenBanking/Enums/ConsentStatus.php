<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Enums;

enum ConsentStatus: string
{
    case AWAITING_AUTHORIZATION = 'awaiting_authorization';
    case AUTHORIZED = 'authorized';
    case REJECTED = 'rejected';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';

    public function isTerminal(): bool
    {
        return in_array($this, [self::REJECTED, self::REVOKED, self::EXPIRED], true);
    }

    public function isActive(): bool
    {
        return $this === self::AUTHORIZED;
    }
}
