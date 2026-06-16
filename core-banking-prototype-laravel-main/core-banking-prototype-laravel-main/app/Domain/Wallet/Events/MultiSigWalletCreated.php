<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new multi-signature wallet is created.
 */
class MultiSigWalletCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $walletId,
        public readonly int $userId,
        public readonly string $name,
        public readonly string $chain,
        public readonly int $requiredSignatures,
        public readonly int $totalSigners,
        public readonly array $metadata = [],
    ) {
    }
}
