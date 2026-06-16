<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Enums;

/**
 * Types of credential issuers in the trust framework.
 */
enum IssuerType: string
{
    case ROOT_CA = 'root_ca';
    case INTERMEDIATE_CA = 'intermediate_ca';
    case ISSUING_CA = 'issuing_ca';
    case TRUSTED_ISSUER = 'trusted_issuer';
    case DELEGATED_ISSUER = 'delegated_issuer';

    public function label(): string
    {
        return match ($this) {
            self::ROOT_CA          => 'Root Certificate Authority',
            self::INTERMEDIATE_CA  => 'Intermediate Certificate Authority',
            self::ISSUING_CA       => 'Issuing Certificate Authority',
            self::TRUSTED_ISSUER   => 'Trusted Issuer',
            self::DELEGATED_ISSUER => 'Delegated Issuer',
        };
    }

    public function canIssue(): bool
    {
        return true;
    }

    public function canDelegateIssuance(): bool
    {
        return in_array($this, [self::ROOT_CA, self::INTERMEDIATE_CA, self::ISSUING_CA], true);
    }

    public function maxChainDepth(): int
    {
        return match ($this) {
            self::ROOT_CA          => 0,
            self::INTERMEDIATE_CA  => 1,
            self::ISSUING_CA       => 2,
            self::TRUSTED_ISSUER   => 3,
            self::DELEGATED_ISSUER => 4,
        };
    }

    /**
     * @return array<TrustLevel>
     */
    public function allowedTrustLevels(): array
    {
        return match ($this) {
            self::ROOT_CA          => [TrustLevel::ULTIMATE],
            self::INTERMEDIATE_CA  => [TrustLevel::HIGH, TrustLevel::ULTIMATE],
            self::ISSUING_CA       => [TrustLevel::VERIFIED, TrustLevel::HIGH],
            self::TRUSTED_ISSUER   => [TrustLevel::BASIC, TrustLevel::VERIFIED],
            self::DELEGATED_ISSUER => [TrustLevel::BASIC],
        };
    }
}
