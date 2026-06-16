<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use App\Domain\TrustCert\ValueObjects\TrustedIssuer;

describe('TrustedIssuer Value Object', function (): void {
    it('creates a trusted issuer', function (): void {
        $registeredAt = new DateTimeImmutable('2024-01-01');

        $issuer = new TrustedIssuer(
            issuerId: 'issuer_123',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'public-key-data',
            registeredAt: $registeredAt,
            metadata: ['org' => 'Test Organization'],
        );

        expect($issuer->issuerId)->toBe('issuer_123');
        expect($issuer->type)->toBe(IssuerType::TRUSTED_ISSUER);
        expect($issuer->trustLevel)->toBe(TrustLevel::VERIFIED);
        expect($issuer->publicKey)->toBe('public-key-data');
        expect($issuer->metadata)->toBe(['org' => 'Test Organization']);
    });

    it('identifies revoked issuers', function (): void {
        $activeIssuer = new TrustedIssuer(
            issuerId: 'issuer_1',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::BASIC,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $revokedIssuer = new TrustedIssuer(
            issuerId: 'issuer_2',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::BASIC,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
            revokedAt: new DateTimeImmutable(),
            revocationReason: 'Compliance violation',
        );

        expect($activeIssuer->isRevoked())->toBeFalse();
        expect($activeIssuer->isTrusted())->toBeTrue();
        expect($revokedIssuer->isRevoked())->toBeTrue();
        expect($revokedIssuer->isTrusted())->toBeFalse();
    });

    it('checks credential issuance capability', function (): void {
        $activeIssuer = new TrustedIssuer(
            issuerId: 'issuer_1',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $revokedIssuer = new TrustedIssuer(
            issuerId: 'issuer_2',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
            revokedAt: new DateTimeImmutable(),
        );

        expect($activeIssuer->canIssueCredentials())->toBeTrue();
        expect($revokedIssuer->canIssueCredentials())->toBeFalse();
    });

    it('checks delegation capability', function (): void {
        $caIssuer = new TrustedIssuer(
            issuerId: 'issuer_1',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        $trustedIssuer = new TrustedIssuer(
            issuerId: 'issuer_2',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        expect($caIssuer->canDelegateIssuance())->toBeTrue();
        expect($trustedIssuer->canDelegateIssuance())->toBeFalse();
    });

    it('checks minimum trust level', function (): void {
        $highTrustIssuer = new TrustedIssuer(
            issuerId: 'issuer_1',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
        );

        expect($highTrustIssuer->meetsMinimumTrustLevel(TrustLevel::BASIC))->toBeTrue();
        expect($highTrustIssuer->meetsMinimumTrustLevel(TrustLevel::HIGH))->toBeTrue();
        expect($highTrustIssuer->meetsMinimumTrustLevel(TrustLevel::ULTIMATE))->toBeFalse();
    });

    it('identifies root issuers', function (): void {
        $rootIssuer = new TrustedIssuer(
            issuerId: 'root',
            type: IssuerType::ROOT_CA,
            trustLevel: TrustLevel::ULTIMATE,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
            parentIssuerId: null,
        );

        $childIssuer = new TrustedIssuer(
            issuerId: 'child',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable(),
            parentIssuerId: 'root',
        );

        expect($rootIssuer->isRootIssuer())->toBeTrue();
        expect($childIssuer->isRootIssuer())->toBeFalse();
    });

    it('generates fingerprint', function (): void {
        $issuer = new TrustedIssuer(
            issuerId: 'issuer_123',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'public-key-data',
            registeredAt: new DateTimeImmutable(),
        );

        $fingerprint = $issuer->getFingerprint();

        expect($fingerprint)->toBeString();
        expect(strlen($fingerprint))->toBe(64);
    });

    it('converts to array', function (): void {
        $issuer = new TrustedIssuer(
            issuerId: 'issuer_123',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            publicKey: 'key',
            registeredAt: new DateTimeImmutable('2024-01-01'),
        );

        $array = $issuer->toArray();

        expect($array)->toHaveKey('issuer_id');
        expect($array)->toHaveKey('type');
        expect($array)->toHaveKey('type_label');
        expect($array)->toHaveKey('trust_level');
        expect($array)->toHaveKey('trust_level_label');
        expect($array['type'])->toBe('trusted_issuer');
    });

    it('creates from array', function (): void {
        $data = [
            'issuer_id'     => 'issuer_123',
            'type'          => 'trusted_issuer',
            'trust_level'   => 'verified',
            'public_key'    => 'key',
            'registered_at' => '2024-01-01T00:00:00+00:00',
        ];

        $issuer = TrustedIssuer::fromArray($data);

        expect($issuer->issuerId)->toBe('issuer_123');
        expect($issuer->type)->toBe(IssuerType::TRUSTED_ISSUER);
        expect($issuer->trustLevel)->toBe(TrustLevel::VERIFIED);
    });
});
