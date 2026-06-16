<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Enums;

enum ReconciliationStatus: string
{
    case PENDING = 'pending';
    case MATCHED = 'matched';
    case DISCREPANCY = 'discrepancy';
    case RESOLVED = 'resolved';
}
