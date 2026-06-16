<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\ValueObjects;

use App\Domain\TrustCert\Enums\RevocationReason;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Represents an entry in the credential revocation registry.
 */
final readonly class RevocationEntry
{
    public function __construct(
        public string $entryId,
        public string $credentialId,
        public RevocationReason $reason,
        public DateTimeInterface $revokedAt,
        public ?string $issuerId = null,
        public ?string $revokedBy = null,
        public ?string $notes = null,
    ) {
    }

    /**
     * Check if this is a permanent revocation.
     */
    public function isPermanent(): bool
    {
        return $this->reason->isPermanent();
    }

    /**
     * Check if this is a temporary hold.
     */
    public function isHold(): bool
    {
        return $this->reason === RevocationReason::CERTIFICATE_HOLD;
    }

    /**
     * Get time since revocation in seconds.
     */
    public function getSecondsSinceRevocation(): int
    {
        return time() - $this->revokedAt->getTimestamp();
    }

    /**
     * Get the revocation hash for integrity verification.
     */
    public function getHash(): string
    {
        $data = [
            'entry_id'      => $this->entryId,
            'credential_id' => $this->credentialId,
            'reason'        => $this->reason->value,
            'revoked_at'    => $this->revokedAt->format('c'),
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'entry_id'      => $this->entryId,
            'credential_id' => $this->credentialId,
            'reason'        => $this->reason->value,
            'reason_label'  => $this->reason->label(),
            'revoked_at'    => $this->revokedAt->format('c'),
            'issuer_id'     => $this->issuerId,
            'revoked_by'    => $this->revokedBy,
            'notes'         => $this->notes,
            'is_permanent'  => $this->isPermanent(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entryId: $data['entry_id'],
            credentialId: $data['credential_id'],
            reason: RevocationReason::from($data['reason']),
            revokedAt: new DateTimeImmutable($data['revoked_at']),
            issuerId: $data['issuer_id'] ?? null,
            revokedBy: $data['revoked_by'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
