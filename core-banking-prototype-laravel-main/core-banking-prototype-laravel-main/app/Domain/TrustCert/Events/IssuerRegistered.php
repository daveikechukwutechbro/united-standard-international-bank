<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Events;

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new issuer is registered in the trust framework.
 */
class IssuerRegistered
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $issuerId,
        public readonly IssuerType $issuerType,
        public readonly TrustLevel $trustLevel,
        public readonly DateTimeInterface $registeredAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'issuer_id'     => $this->issuerId,
            'issuer_type'   => $this->issuerType->value,
            'trust_level'   => $this->trustLevel->value,
            'registered_at' => $this->registeredAt->format('c'),
        ];
    }
}
