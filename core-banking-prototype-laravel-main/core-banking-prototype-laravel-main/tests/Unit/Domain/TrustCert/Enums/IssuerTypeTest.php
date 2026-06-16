<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;

describe('IssuerType Enum', function (): void {
    it('has all expected types', function (): void {
        $types = IssuerType::cases();

        expect($types)->toHaveCount(5);
        expect(IssuerType::ROOT_CA->value)->toBe('root_ca');
        expect(IssuerType::INTERMEDIATE_CA->value)->toBe('intermediate_ca');
        expect(IssuerType::ISSUING_CA->value)->toBe('issuing_ca');
        expect(IssuerType::TRUSTED_ISSUER->value)->toBe('trusted_issuer');
        expect(IssuerType::DELEGATED_ISSUER->value)->toBe('delegated_issuer');
    });

    it('returns correct labels', function (): void {
        expect(IssuerType::ROOT_CA->label())->toBe('Root Certificate Authority');
        expect(IssuerType::INTERMEDIATE_CA->label())->toBe('Intermediate Certificate Authority');
        expect(IssuerType::TRUSTED_ISSUER->label())->toBe('Trusted Issuer');
    });

    it('all types can issue', function (): void {
        foreach (IssuerType::cases() as $type) {
            expect($type->canIssue())->toBeTrue();
        }
    });

    it('correctly identifies delegation capability', function (): void {
        expect(IssuerType::ROOT_CA->canDelegateIssuance())->toBeTrue();
        expect(IssuerType::INTERMEDIATE_CA->canDelegateIssuance())->toBeTrue();
        expect(IssuerType::ISSUING_CA->canDelegateIssuance())->toBeTrue();
        expect(IssuerType::TRUSTED_ISSUER->canDelegateIssuance())->toBeFalse();
        expect(IssuerType::DELEGATED_ISSUER->canDelegateIssuance())->toBeFalse();
    });

    it('returns correct max chain depth', function (): void {
        expect(IssuerType::ROOT_CA->maxChainDepth())->toBe(0);
        expect(IssuerType::INTERMEDIATE_CA->maxChainDepth())->toBe(1);
        expect(IssuerType::ISSUING_CA->maxChainDepth())->toBe(2);
        expect(IssuerType::TRUSTED_ISSUER->maxChainDepth())->toBe(3);
        expect(IssuerType::DELEGATED_ISSUER->maxChainDepth())->toBe(4);
    });

    it('returns allowed trust levels', function (): void {
        $rootLevels = IssuerType::ROOT_CA->allowedTrustLevels();
        expect($rootLevels)->toContain(TrustLevel::ULTIMATE);

        $trustedLevels = IssuerType::TRUSTED_ISSUER->allowedTrustLevels();
        expect($trustedLevels)->toContain(TrustLevel::BASIC);
        expect($trustedLevels)->toContain(TrustLevel::VERIFIED);
        expect($trustedLevels)->not->toContain(TrustLevel::ULTIMATE);
    });
});
