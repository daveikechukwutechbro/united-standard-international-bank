<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Events;

use App\Domain\CardIssuance\Enums\AuthorizationDecision;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a card authorization is declined.
 */
final class AuthorizationDeclined
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $authorizationId,
        public readonly string $cardToken,
        public readonly float $amount,
        public readonly string $currency,
        public readonly AuthorizationDecision $reason,
        public readonly string $merchantName,
    ) {
    }
}
