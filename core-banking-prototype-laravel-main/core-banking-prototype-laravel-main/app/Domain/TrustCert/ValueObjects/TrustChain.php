<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\ValueObjects;

use App\Domain\TrustCert\Enums\TrustLevel;

/**
 * Represents a chain of trust from a credential to a root issuer.
 */
final readonly class TrustChain
{
    /**
     * @param array<TrustedIssuer> $chain
     */
    public function __construct(
        public string $credentialId,
        public array $chain,
        public bool $isComplete,
        public ?string $validationError = null,
    ) {
    }

    public function isValid(): bool
    {
        return $this->isComplete && $this->validationError === null;
    }

    public function getDepth(): int
    {
        return count($this->chain);
    }

    public function getRootIssuer(): ?TrustedIssuer
    {
        if (empty($this->chain)) {
            return null;
        }

        return $this->chain[array_key_last($this->chain)];
    }

    public function getImmediateIssuer(): ?TrustedIssuer
    {
        if (empty($this->chain)) {
            return null;
        }

        return $this->chain[0];
    }

    /**
     * Get the minimum trust level in the chain.
     */
    public function getMinimumTrustLevel(): ?TrustLevel
    {
        if (empty($this->chain)) {
            return null;
        }

        $minLevel = TrustLevel::ULTIMATE;

        foreach ($this->chain as $issuer) {
            if ($issuer->trustLevel->numericValue() < $minLevel->numericValue()) {
                $minLevel = $issuer->trustLevel;
            }
        }

        return $minLevel;
    }

    /**
     * Check if all issuers in the chain meet a minimum trust level.
     */
    public function meetsMinimumTrustLevel(TrustLevel $required): bool
    {
        foreach ($this->chain as $issuer) {
            if (! $issuer->meetsMinimumTrustLevel($required)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function getIssuerIds(): array
    {
        return array_map(fn (TrustedIssuer $issuer) => $issuer->issuerId, $this->chain);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'credential_id'       => $this->credentialId,
            'chain'               => array_map(fn (TrustedIssuer $i) => $i->toArray(), $this->chain),
            'depth'               => $this->getDepth(),
            'is_complete'         => $this->isComplete,
            'is_valid'            => $this->isValid(),
            'validation_error'    => $this->validationError,
            'minimum_trust_level' => $this->getMinimumTrustLevel()?->value,
        ];
    }
}
