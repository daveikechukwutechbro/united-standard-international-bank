<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Contracts;

use App\Domain\TrustCert\Enums\RevocationReason;
use App\Domain\TrustCert\ValueObjects\RevocationEntry;
use DateTimeInterface;

/**
 * Contract for credential revocation registry operations.
 */
interface RevocationRegistryInterface
{
    /**
     * Add a credential to the revocation list.
     */
    public function revoke(
        string $credentialId,
        RevocationReason $reason,
        ?string $revokedBy = null,
    ): RevocationEntry;

    /**
     * Check if a credential is revoked.
     */
    public function isRevoked(string $credentialId): bool;

    /**
     * Get revocation details for a credential.
     */
    public function getRevocationEntry(string $credentialId): ?RevocationEntry;

    /**
     * Get all revocations by issuer.
     *
     * @return array<RevocationEntry>
     */
    public function getRevocationsByIssuer(string $issuerId): array;

    /**
     * Get revocations since a specific timestamp.
     *
     * @return array<RevocationEntry>
     */
    public function getRevocationsSince(DateTimeInterface $since): array;

    /**
     * Get total revocation count.
     */
    public function getRevocationCount(): int;

    /**
     * Generate a revocation list hash for integrity verification.
     */
    public function generateRevocationListHash(): string;
}
