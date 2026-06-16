<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Contracts;

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use App\Domain\TrustCert\ValueObjects\TrustChain;
use App\Domain\TrustCert\ValueObjects\TrustedIssuer;

/**
 * Contract for trust framework operations.
 */
interface TrustFrameworkInterface
{
    /**
     * Register an issuer in the trust framework.
     *
     * @param array<string, mixed> $metadata
     */
    public function registerIssuer(
        string $issuerId,
        IssuerType $type,
        TrustLevel $trustLevel,
        array $metadata = [],
    ): TrustedIssuer;

    /**
     * Update an issuer's trust level.
     */
    public function updateTrustLevel(string $issuerId, TrustLevel $newLevel): bool;

    /**
     * Revoke an issuer's trust status.
     */
    public function revokeIssuer(string $issuerId, string $reason): bool;

    /**
     * Check if an issuer is trusted.
     */
    public function isIssuerTrusted(string $issuerId): bool;

    /**
     * Get issuer details.
     */
    public function getIssuer(string $issuerId): ?TrustedIssuer;

    /**
     * Get issuer's trust level.
     */
    public function getIssuerTrustLevel(string $issuerId): ?TrustLevel;

    /**
     * Verify a credential's issuer chain of trust.
     */
    public function verifyIssuerChain(string $issuerId): bool;

    /**
     * Get all issuers at or above a trust level.
     *
     * @return array<TrustedIssuer>
     */
    public function getIssuersByTrustLevel(TrustLevel $minimumLevel): array;

    /**
     * Build the trust chain for a credential's issuer.
     */
    public function buildTrustChain(string $credentialId, string $issuerId): TrustChain;
}
