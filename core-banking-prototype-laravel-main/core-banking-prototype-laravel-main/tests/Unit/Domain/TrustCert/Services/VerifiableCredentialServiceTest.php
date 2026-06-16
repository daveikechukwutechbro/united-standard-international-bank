<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\RevocationReason;
use App\Domain\TrustCert\Enums\TrustLevel;
use App\Domain\TrustCert\Services\RevocationRegistryService;
use App\Domain\TrustCert\Services\TrustFrameworkService;
use App\Domain\TrustCert\Services\VerifiableCredentialService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

describe('VerifiableCredentialService', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        Event::fake();
        $this->revocationRegistry = new RevocationRegistryService();
        $this->trustFramework = new TrustFrameworkService();
        $this->service = new VerifiableCredentialService(
            $this->revocationRegistry,
            $this->trustFramework,
            'did:finaegis:issuer:test',
            'test-signing-key',
        );
    });

    it('issues verifiable credentials', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe', 'role' => 'admin'],
            types: ['VerifiableCredential', 'IdentityCredential'],
        );

        expect($credential)->toHaveKey('@context');
        expect($credential)->toHaveKey('id');
        expect($credential)->toHaveKey('type');
        expect($credential)->toHaveKey('issuer');
        expect($credential)->toHaveKey('issuanceDate');
        expect($credential)->toHaveKey('credentialSubject');
        expect($credential)->toHaveKey('proof');
        expect($credential['issuer'])->toBe('did:finaegis:issuer:test');
        expect($credential['credentialSubject']['id'])->toBe('did:finaegis:user:123');
        expect($credential['credentialSubject']['name'])->toBe('John Doe');
    });

    it('issues credentials with expiration', function (): void {
        $expiration = new DateTimeImmutable('+1 year');

        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
            expirationDate: $expiration,
        );

        expect($credential)->toHaveKey('expirationDate');
    });

    it('verifies valid credentials', function (): void {
        $this->trustFramework->registerIssuer(
            'did:finaegis:issuer:test',
            IssuerType::TRUSTED_ISSUER,
            TrustLevel::VERIFIED,
        );

        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $result = $this->service->verifyCredential($credential);

        expect($result['valid'])->toBeTrue();
        expect($result['errors'])->toBeEmpty();
    });

    it('detects expired credentials', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
            expirationDate: new DateTimeImmutable('-1 day'),
        );

        $result = $this->service->verifyCredential($credential);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toContain('Credential has expired');
    });

    it('detects revoked credentials', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $this->revocationRegistry->revoke($credential['id'], RevocationReason::KEY_COMPROMISE);

        $result = $this->service->verifyCredential($credential);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toContain('Credential has been revoked');
    });

    it('warns about untrusted issuers', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $result = $this->service->verifyCredential($credential);

        expect($result['warnings'])->toContain('Issuer is not in the trusted issuer registry');
    });

    it('creates verifiable presentations', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $presentation = $this->service->createPresentation(
            credentials: [$credential],
            holder: 'did:finaegis:user:123',
            challenge: 'test-challenge-123',
            domain: 'https://example.com',
        );

        expect($presentation)->toHaveKey('@context');
        expect($presentation)->toHaveKey('type');
        expect($presentation)->toHaveKey('holder');
        expect($presentation)->toHaveKey('verifiableCredential');
        expect($presentation)->toHaveKey('proof');
        expect($presentation['holder'])->toBe('did:finaegis:user:123');
        expect($presentation['proof']['challenge'])->toBe('test-challenge-123');
        expect($presentation['proof']['domain'])->toBe('https://example.com');
    });

    it('verifies valid presentations', function (): void {
        $this->trustFramework->registerIssuer(
            'did:finaegis:issuer:test',
            IssuerType::TRUSTED_ISSUER,
            TrustLevel::VERIFIED,
        );

        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $presentation = $this->service->createPresentation(
            credentials: [$credential],
            holder: 'did:finaegis:user:123',
            challenge: 'test-challenge',
        );

        $result = $this->service->verifyPresentation(
            $presentation,
            expectedChallenge: 'test-challenge',
        );

        expect($result['valid'])->toBeTrue();
        expect($result['holder'])->toBe('did:finaegis:user:123');
    });

    it('detects challenge mismatch', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $presentation = $this->service->createPresentation(
            credentials: [$credential],
            holder: 'did:finaegis:user:123',
            challenge: 'original-challenge',
        );

        $result = $this->service->verifyPresentation(
            $presentation,
            expectedChallenge: 'different-challenge',
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toContain('Challenge mismatch');
    });

    it('revokes credentials', function (): void {
        $credential = $this->service->issueCredential(
            subjectId: 'did:finaegis:user:123',
            credentialSubject: ['name' => 'John Doe'],
        );

        $revoked = $this->service->revokeCredential($credential['id'], RevocationReason::SUPERSEDED);

        expect($revoked)->toBeTrue();
        expect($this->revocationRegistry->isRevoked($credential['id']))->toBeTrue();
    });

    it('builds trust chains', function (): void {
        $this->trustFramework->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->trustFramework->registerDelegatedIssuer(
            'did:finaegis:issuer:test',
            'root',
            IssuerType::ISSUING_CA,
            TrustLevel::HIGH,
        );

        $chain = $this->service->buildTrustChain('cred_123', 'did:finaegis:issuer:test');

        expect($chain->isValid())->toBeTrue();
        expect($chain->getDepth())->toBe(2);
    });

    it('checks minimum trust level', function (): void {
        $this->trustFramework->registerIssuer(
            'did:finaegis:issuer:test',
            IssuerType::TRUSTED_ISSUER,
            TrustLevel::VERIFIED,
        );

        expect($this->service->meetsMinimumTrustLevel('did:finaegis:issuer:test', TrustLevel::BASIC))->toBeTrue();
        expect($this->service->meetsMinimumTrustLevel('did:finaegis:issuer:test', TrustLevel::VERIFIED))->toBeTrue();
        expect($this->service->meetsMinimumTrustLevel('did:finaegis:issuer:test', TrustLevel::HIGH))->toBeFalse();
    });

    it('returns false for unknown issuer trust level', function (): void {
        expect($this->service->meetsMinimumTrustLevel('unknown', TrustLevel::BASIC))->toBeFalse();
    });
});
