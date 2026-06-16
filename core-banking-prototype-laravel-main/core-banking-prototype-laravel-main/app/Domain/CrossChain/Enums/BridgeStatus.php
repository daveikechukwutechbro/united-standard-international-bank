<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Enums;

/**
 * Bridge transaction lifecycle status.
 */
enum BridgeStatus: string
{
    case INITIATED = 'initiated';
    case BRIDGING = 'bridging';
    case CONFIRMING = 'confirming';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::REFUNDED]);
    }

    public function isPending(): bool
    {
        return in_array($this, [self::INITIATED, self::BRIDGING, self::CONFIRMING]);
    }
}
