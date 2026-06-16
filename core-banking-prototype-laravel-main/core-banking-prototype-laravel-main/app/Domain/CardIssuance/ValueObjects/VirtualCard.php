<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\ValueObjects;

use App\Domain\CardIssuance\Enums\CardNetwork;
use App\Domain\CardIssuance\Enums\CardStatus;
use DateTimeImmutable;

/**
 * Represents a virtual card.
 */
final readonly class VirtualCard
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $cardToken,
        public string $last4,
        public CardNetwork $network,
        public CardStatus $status,
        public string $cardholderName,
        public DateTimeImmutable $expiresAt,
        public ?string $pan = null,          // Only available in secure contexts
        public ?string $cvv = null,          // Only available in secure contexts
        public array $metadata = [],
        public ?string $label = null,
    ) {
    }

    public function isUsable(): bool
    {
        return $this->status->isUsable() && $this->expiresAt > new DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'card_token'      => $this->cardToken,
            'last4'           => $this->last4,
            'network'         => $this->network->value,
            'status'          => $this->status->value,
            'cardholder_name' => $this->cardholderName,
            'expires_at'      => $this->expiresAt->format('Y-m-d'),
            'label'           => $this->label,
            'metadata'        => $this->metadata,
        ];
    }
}
