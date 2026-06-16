<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Events;

use App\Domain\CardIssuance\Enums\WalletType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a card is provisioned to Apple Pay / Google Pay.
 */
final class CardProvisioned
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $cardToken,
        public readonly WalletType $walletType,
        public readonly string $deviceId,
    ) {
    }
}
