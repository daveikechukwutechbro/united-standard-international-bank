<?php

declare(strict_types=1);

namespace App\Domain\Relayer\Events;

use App\Domain\Relayer\Enums\SupportedNetwork;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a transaction is sponsored by the gas station.
 */
final class TransactionSponsored
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userAddress,
        public readonly string $userOpHash,
        public readonly SupportedNetwork $network,
        public readonly float $feeAmount,
        public readonly string $feeToken,
    ) {
    }
}
