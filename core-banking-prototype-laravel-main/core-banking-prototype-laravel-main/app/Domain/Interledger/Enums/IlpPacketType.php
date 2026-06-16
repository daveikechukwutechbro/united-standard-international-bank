<?php

declare(strict_types=1);

namespace App\Domain\Interledger\Enums;

/**
 * ILP packet types in the Interledger Protocol STREAM.
 *
 * Flow: PREPARE -> FULFILL (success) or PREPARE -> REJECT (failure)
 */
enum IlpPacketType: string
{
    case PREPARE = 'prepare';
    case FULFILL = 'fulfill';
    case REJECT = 'reject';

    /**
     * Get a human-readable label for this packet type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PREPARE => 'Prepare',
            self::FULFILL => 'Fulfill',
            self::REJECT  => 'Reject',
        };
    }
}
