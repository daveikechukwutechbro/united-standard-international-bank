<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a card authorization is approved.
 */
final class AuthorizationApproved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $authorizationId,
        public readonly string $cardToken,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $holdId,
        public readonly string $merchantName,
    ) {
    }
}
