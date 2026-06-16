<?php

declare(strict_types=1);

namespace App\Domain\ISO20022\Enums;

enum TransactionStatus: string
{
    case ACCP = 'ACCP';
    case ACSP = 'ACSP';
    case ACSC = 'ACSC';
    case ACTC = 'ACTC';
    case ACWC = 'ACWC';
    case PDNG = 'PDNG';
    case RCVD = 'RCVD';
    case RJCT = 'RJCT';
    case CANC = 'CANC';

    public function isTerminal(): bool
    {
        return in_array($this, [self::ACSC, self::RJCT, self::CANC], true);
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::ACSC, self::ACSP, self::ACCP], true);
    }
}
