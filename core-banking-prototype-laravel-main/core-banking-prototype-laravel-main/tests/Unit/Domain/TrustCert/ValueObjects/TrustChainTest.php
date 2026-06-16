<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use App\Domain\TrustCert\ValueObjects\TrustChain;
use App\Domain\TrustCert\ValueObjects\TrustedIssuer;

describe('TrustChain Value Object', function (): void {
    it('creates a valid trust chain', function (): void {
        $rootIssuer = new TrustedIssuer(
            issuerId: 'root',
            type: IssuerType::ROOT_CA,
            trustLevel: TrustLevel::ULTIMATE,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $intermediateIssuer = new TrustedIssuer(
            issuerId: 'intermediate',
            type: IssuerType::INTERMEDIATE_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
            parentIssuerId: 'root',
        );

        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [$intermediateIssuer, $rootIssuer],
            isComplete: true,
        );

        expect($chain->credentialId)->toBe('cred_123');
        expect($chain->isComplete)->toBeTrue();
        expect($chain->isValid())->toBeTrue();
        expect($chain->getDepth())->toBe(2);
    });

    it('identifies invalid chains', function (): void {
        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [],
            isComplete: false,
            validationError: 'Issuer not found',
        );

        expect($chain->isValid())->toBeFalse();
        expect($chain->validationError)->toBe('Issuer not found');
    });

    it('gets root and immediate issuers', function (): void {
        $rootIssuer = new TrustedIssuer(
            issuerId: 'root',
            type: IssuerType::ROOT_CA,
            trustLevel: TrustLevel::ULTIMATE,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $immediateIssuer = new TrustedIssuer(
            issuerId: 'immediate',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
            parentIssuerId: 'root',
        );

        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [$immediateIssuer, $rootIssuer],
            isComplete: true,
        );

        expect($chain->getImmediateIssuer()->issuerId)->toBe('immediate');
        expect($chain->getRootIssuer()->issuerId)->toBe('root');
    });

    it('returns null for empty chain', function (): void {
        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [],
            isComplete: false,
        );

        expect($chain->getImmediateIssuer())->toBeNull();
        expect($chain->getRootIssuer())->toBeNull();
        expect($chain->getMinimumTrustLevel())->toBeNull();
    });

    it('calculates minimum trust level', function (): void {
        $highTrustIssuer = new TrustedIssuer(
            issuerId: 'high',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $verifiedTrustIssuer = new TrustedIssuer(
            issuerId: 'verified',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [$verifiedTrustIssuer, $highTrustIssuer],
            isComplete: true,
        );

        expect($chain->getMinimumTrustLevel())->toBe(TrustLevel::VERIFIED);
    });

    it('checks minimum trust level requirement', function (): void {
        $highTrustIssuer = new TrustedIssuer(
            issuerId: 'high',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $verifiedTrustIssuer = new TrustedIssuer(
            issuerId: 'verified',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [$verifiedTrustIssuer, $highTrustIssuer],
            isComplete: true,
        );

        expect($chain->meetsMinimumTrustLevel(TrustLevel::BASIC))->toBeTrue();
        expect($chain->meetsMinimumTrustLevel(TrustLevel::VERIFIED))->toBeTrue();
        expect($chain->meetsMinimumTrustLevel(TrustLevel::HIGH))->toBeFalse();
    });

    it('gets issuer IDs', function (): void {
        $issuer1 = new TrustedIssuer(
            issuerId: 'issuer_1',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $issuer2 = new TrustedIssuer(
            issuerId: 'issuer_2',
            type: IssuerType::ROOT_CA,
            trustLevel: TrustLevel::ULTIMATE,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [$issuer1, $issuer2],
            isComplete: true,
        );

        $ids = $chain->getIssuerIds();

        expect($ids)->toBe(['issuer_1', 'issuer_2']);
    });

    it('converts to array', function (): void {
        $issuer = new TrustedIssuer(
            issuerId: 'issuer_1',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $chain = new TrustChain(
            credentialId: 'cred_123',
            chain: [$issuer],
            isComplete: true,
        );

        $array = $chain->toArray();

        expect($array)->toHaveKey('credential_id');
        expect($array)->toHaveKey('chain');
        expect($array)->toHaveKey('depth');
        expect($array)->toHaveKey('is_complete');
        expect($array)->toHaveKey('is_valid');
        expect($array)->toHaveKey('minimum_trust_level');
        expect($array['depth'])->toBe(1);
        expect($array['is_valid'])->toBeTrue();
    });
});
