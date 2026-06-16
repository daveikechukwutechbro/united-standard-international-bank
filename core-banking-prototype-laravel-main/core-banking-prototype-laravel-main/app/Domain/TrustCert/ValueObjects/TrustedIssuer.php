<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\ValueObjects;

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Represents a trusted issuer in the trust framework.
 */
final readonly class TrustedIssuer
{
    public function __construct(
        public string $issuerId,
        public IssuerType $type,
        public TrustLevel $trustLevel,
        public string $publicKey,
        public DateTimeInterface $registeredAt,
        /** @var array<string, mixed> */
        public array $metadata = [],
        public ?string $parentIssuerId = null,
        public ?DateTimeInterface $revokedAt = null,
        public ?string $revocationReason = null,
    ) {
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isTrusted(): bool
    {
        return ! $this->isRevoked();
    }

    public function canIssueCredentials(): bool
    {
        return $this->isTrusted() && $this->type->canIssue();
    }

    public function canDelegateIssuance(): bool
    {
        return $this->isTrusted() && $this->type->canDelegateIssuance();
    }

    public function meetsMinimumTrustLevel(TrustLevel $required): bool
    {
        return $this->trustLevel->meetsMinimum($required);
    }

    public function isRootIssuer(): bool
    {
        return $this->parentIssuerId === null;
    }

    /**
     * Get issuer fingerprint.
     */
    public function getFingerprint(): string
    {
        $data = [
            'issuer_id'  => $this->issuerId,
            'type'       => $this->type->value,
            'public_key' => $this->publicKey,
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'issuer_id'         => $this->issuerId,
            'type'              => $this->type->value,
            'type_label'        => $this->type->label(),
            'trust_level'       => $this->trustLevel->value,
            'trust_level_label' => $this->trustLevel->label(),
            'public_key'        => $this->publicKey,
            'registered_at'     => $this->registeredAt->format('c'),
            'metadata'          => $this->metadata,
            'parent_issuer_id'  => $this->parentIssuerId,
            'revoked_at'        => $this->revokedAt?->format('c'),
            'revocation_reason' => $this->revocationReason,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            issuerId: $data['issuer_id'],
            type: IssuerType::from($data['type']),
            trustLevel: TrustLevel::from($data['trust_level']),
            publicKey: $data['public_key'],
            registeredAt: new DateTimeImmutable($data['registered_at']),
            metadata: $data['metadata'] ?? [],
            parentIssuerId: $data['parent_issuer_id'] ?? null,
            revokedAt: isset($data['revoked_at']) ? new DateTimeImmutable($data['revoked_at']) : null,
            revocationReason: $data['revocation_reason'] ?? null,
        );
    }
}
