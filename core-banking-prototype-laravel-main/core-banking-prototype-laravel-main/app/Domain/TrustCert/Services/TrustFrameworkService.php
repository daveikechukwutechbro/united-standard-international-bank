<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Services;

use App\Domain\TrustCert\Contracts\TrustFrameworkInterface;
use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use App\Domain\TrustCert\Events\IssuerRegistered;
use App\Domain\TrustCert\Events\TrustLevelChanged;
use App\Domain\TrustCert\ValueObjects\TrustChain;
use App\Domain\TrustCert\ValueObjects\TrustedIssuer;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service for managing multi-issuer trust framework.
 */
class TrustFrameworkService implements TrustFrameworkInterface
{
    private const CACHE_PREFIX = 'trust_issuer:';

    private const CACHE_CHILD_PREFIX = 'trust_children:';

    /** @var array<string, TrustedIssuer> */
    private array $issuers = [];

    /** @var array<string, array<string>> */
    private array $childIndex = [];

    /**
     * {@inheritDoc}
     */
    public function registerIssuer(
        string $issuerId,
        IssuerType $type,
        TrustLevel $trustLevel,
        array $metadata = [],
    ): TrustedIssuer {
        if (isset($this->issuers[$issuerId])) {
            throw new InvalidArgumentException("Issuer {$issuerId} is already registered");
        }

        // Validate trust level for issuer type
        $allowedLevels = $type->allowedTrustLevels();
        if (! in_array($trustLevel, $allowedLevels, true)) {
            throw new InvalidArgumentException(
                "Trust level {$trustLevel->value} is not allowed for issuer type {$type->value}",
            );
        }

        $publicKey = $this->generateIssuerPublicKey($issuerId);
        $registeredAt = new DateTimeImmutable();

        $issuer = new TrustedIssuer(
            issuerId: $issuerId,
            type: $type,
            trustLevel: $trustLevel,
            publicKey: $publicKey,
            registeredAt: $registeredAt,
            metadata: $metadata,
        );

        // Persist in both memory and cache
        $this->issuers[$issuerId] = $issuer;
        Cache::put(self::CACHE_PREFIX . $issuerId, $issuer);

        Event::dispatch(new IssuerRegistered(
            issuerId: $issuerId,
            issuerType: $type,
            trustLevel: $trustLevel,
            registeredAt: $registeredAt,
        ));

        return $issuer;
    }

    /**
     * Register a delegated issuer under a parent.
     *
     * @param array<string, mixed> $metadata
     */
    public function registerDelegatedIssuer(
        string $issuerId,
        string $parentIssuerId,
        IssuerType $type,
        TrustLevel $trustLevel,
        array $metadata = [],
    ): TrustedIssuer {
        // Validate parent exists and can delegate
        $parent = $this->getIssuer($parentIssuerId);
        if ($parent === null) {
            throw new InvalidArgumentException("Parent issuer {$parentIssuerId} not found");
        }

        if (! $parent->canDelegateIssuance()) {
            throw new InvalidArgumentException("Parent issuer {$parentIssuerId} cannot delegate issuance");
        }

        // Child trust level cannot exceed parent
        if ($trustLevel->numericValue() > $parent->trustLevel->numericValue()) {
            throw new InvalidArgumentException('Delegated issuer trust level cannot exceed parent');
        }

        if (isset($this->issuers[$issuerId])) {
            throw new InvalidArgumentException("Issuer {$issuerId} is already registered");
        }

        $publicKey = $this->generateIssuerPublicKey($issuerId);
        $registeredAt = new DateTimeImmutable();

        $issuer = new TrustedIssuer(
            issuerId: $issuerId,
            type: $type,
            trustLevel: $trustLevel,
            publicKey: $publicKey,
            registeredAt: $registeredAt,
            metadata: $metadata,
            parentIssuerId: $parentIssuerId,
        );

        // Persist in both memory and cache
        $this->issuers[$issuerId] = $issuer;
        Cache::put(self::CACHE_PREFIX . $issuerId, $issuer);

        // Index parent-child relationship
        if (! isset($this->childIndex[$parentIssuerId])) {
            $this->childIndex[$parentIssuerId] = [];
        }
        $this->childIndex[$parentIssuerId][] = $issuerId;
        Cache::put(self::CACHE_CHILD_PREFIX . $parentIssuerId, $this->childIndex[$parentIssuerId]);

        Event::dispatch(new IssuerRegistered(
            issuerId: $issuerId,
            issuerType: $type,
            trustLevel: $trustLevel,
            registeredAt: $registeredAt,
        ));

        return $issuer;
    }

    /**
     * {@inheritDoc}
     */
    public function updateTrustLevel(string $issuerId, TrustLevel $newLevel): bool
    {
        $issuer = $this->getIssuer($issuerId);
        if ($issuer === null) {
            return false;
        }

        if ($issuer->isRevoked()) {
            return false;
        }

        // Validate new trust level for issuer type
        $allowedLevels = $issuer->type->allowedTrustLevels();
        if (! in_array($newLevel, $allowedLevels, true)) {
            return false;
        }

        $previousLevel = $issuer->trustLevel;

        // Create updated issuer
        $updatedIssuer = new TrustedIssuer(
            issuerId: $issuer->issuerId,
            type: $issuer->type,
            trustLevel: $newLevel,
            publicKey: $issuer->publicKey,
            registeredAt: $issuer->registeredAt,
            metadata: $issuer->metadata,
            parentIssuerId: $issuer->parentIssuerId,
        );

        // Persist in both memory and cache
        $this->issuers[$issuerId] = $updatedIssuer;
        Cache::put(self::CACHE_PREFIX . $issuerId, $updatedIssuer);

        Event::dispatch(new TrustLevelChanged(
            issuerId: $issuerId,
            previousLevel: $previousLevel,
            newLevel: $newLevel,
            changedAt: new DateTimeImmutable(),
        ));

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function revokeIssuer(string $issuerId, string $reason): bool
    {
        $issuer = $this->getIssuer($issuerId);
        if ($issuer === null) {
            return false;
        }

        if ($issuer->isRevoked()) {
            return false;
        }

        $revokedAt = new DateTimeImmutable();

        $revokedIssuer = new TrustedIssuer(
            issuerId: $issuer->issuerId,
            type: $issuer->type,
            trustLevel: $issuer->trustLevel,
            publicKey: $issuer->publicKey,
            registeredAt: $issuer->registeredAt,
            metadata: $issuer->metadata,
            parentIssuerId: $issuer->parentIssuerId,
            revokedAt: $revokedAt,
            revocationReason: $reason,
        );

        // Persist in both memory and cache
        $this->issuers[$issuerId] = $revokedIssuer;
        Cache::put(self::CACHE_PREFIX . $issuerId, $revokedIssuer);

        // Also revoke child issuers
        $this->revokeChildIssuers($issuerId, 'Parent issuer revoked');

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isIssuerTrusted(string $issuerId): bool
    {
        $issuer = $this->getIssuer($issuerId);
        if ($issuer === null) {
            return false;
        }

        return $issuer->isTrusted();
    }

    /**
     * {@inheritDoc}
     */
    public function getIssuer(string $issuerId): ?TrustedIssuer
    {
        return $this->issuers[$issuerId]
            ?? Cache::get(self::CACHE_PREFIX . $issuerId);
    }

    /**
     * {@inheritDoc}
     */
    public function getIssuerTrustLevel(string $issuerId): ?TrustLevel
    {
        $issuer = $this->getIssuer($issuerId);

        return $issuer?->trustLevel;
    }

    /**
     * {@inheritDoc}
     */
    public function verifyIssuerChain(string $issuerId): bool
    {
        $issuer = $this->getIssuer($issuerId);
        if ($issuer === null) {
            return false;
        }

        if (! $issuer->isTrusted()) {
            return false;
        }

        // If root issuer, we're done
        if ($issuer->isRootIssuer()) {
            return true;
        }

        // Verify parent chain recursively (parentIssuerId is not null for non-root issuers)
        /** @var string $parentIssuerId */
        $parentIssuerId = $issuer->parentIssuerId;

        return $this->verifyIssuerChain($parentIssuerId);
    }

    /**
     * {@inheritDoc}
     */
    public function getIssuersByTrustLevel(TrustLevel $minimumLevel): array
    {
        return array_filter(
            $this->issuers,
            fn (TrustedIssuer $issuer) => $issuer->isTrusted() && $issuer->meetsMinimumTrustLevel($minimumLevel),
        );
    }

    /**
     * Build the trust chain for a credential's issuer.
     */
    public function buildTrustChain(string $credentialId, string $issuerId): TrustChain
    {
        $chain = [];
        $currentIssuerId = $issuerId;
        $maxDepth = 10; // Prevent infinite loops
        $depth = 0;

        while ($currentIssuerId !== null && $depth < $maxDepth) {
            $issuer = $this->getIssuer($currentIssuerId);
            if ($issuer === null) {
                return new TrustChain(
                    credentialId: $credentialId,
                    chain: $chain,
                    isComplete: false,
                    validationError: "Issuer {$currentIssuerId} not found in trust framework",
                );
            }

            if ($issuer->isRevoked()) {
                return new TrustChain(
                    credentialId: $credentialId,
                    chain: $chain,
                    isComplete: false,
                    validationError: "Issuer {$currentIssuerId} has been revoked",
                );
            }

            $chain[] = $issuer;
            $currentIssuerId = $issuer->parentIssuerId;
            $depth++;
        }

        if ($depth >= $maxDepth) {
            return new TrustChain(
                credentialId: $credentialId,
                chain: $chain,
                isComplete: false,
                validationError: 'Maximum chain depth exceeded',
            );
        }

        return new TrustChain(
            credentialId: $credentialId,
            chain: $chain,
            isComplete: true,
        );
    }

    /**
     * Get all child issuers of a parent.
     *
     * @return array<TrustedIssuer>
     */
    public function getChildIssuers(string $parentIssuerId): array
    {
        $childIds = $this->childIndex[$parentIssuerId]
            ?? Cache::get(self::CACHE_CHILD_PREFIX . $parentIssuerId, []);

        return array_filter(array_map(
            fn (string $id) => $this->getIssuer($id),
            $childIds,
        ));
    }

    /**
     * Get all issuers.
     *
     * @return array<TrustedIssuer>
     */
    public function getAllIssuers(): array
    {
        return array_values($this->issuers);
    }

    /**
     * Get all root issuers.
     *
     * @return array<TrustedIssuer>
     */
    public function getRootIssuers(): array
    {
        return array_filter(
            $this->issuers,
            fn (TrustedIssuer $issuer) => $issuer->isRootIssuer() && $issuer->isTrusted(),
        );
    }

    private function revokeChildIssuers(string $parentIssuerId, string $reason): void
    {
        $childIds = $this->childIndex[$parentIssuerId]
            ?? Cache::get(self::CACHE_CHILD_PREFIX . $parentIssuerId, []);

        foreach ($childIds as $childId) {
            $child = $this->getIssuer($childId);
            if ($child !== null && ! $child->isRevoked()) {
                $this->revokeIssuer($childId, $reason);
            }
        }
    }

    private function generateIssuerPublicKey(string $issuerId): string
    {
        $data = $issuerId . ':' . Str::uuid()->toString() . ':' . time();

        return base64_encode(hash('sha256', $data, true));
    }
}
