<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\IssuerType;
use App\Domain\TrustCert\Enums\TrustLevel;
use App\Domain\TrustCert\Events\IssuerRegistered;
use App\Domain\TrustCert\Events\TrustLevelChanged;
use App\Domain\TrustCert\Services\TrustFrameworkService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

describe('TrustFrameworkService', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        Event::fake();
        $this->service = new TrustFrameworkService();
    });

    it('registers issuers', function (): void {
        $issuer = $this->service->registerIssuer(
            issuerId: 'issuer_123',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::VERIFIED,
            metadata: ['org' => 'Test Org'],
        );

        expect($issuer->issuerId)->toBe('issuer_123');
        expect($issuer->type)->toBe(IssuerType::TRUSTED_ISSUER);
        expect($issuer->trustLevel)->toBe(TrustLevel::VERIFIED);
        expect($issuer->metadata)->toBe(['org' => 'Test Org']);

        Event::assertDispatched(IssuerRegistered::class);
    });

    it('prevents duplicate issuer registration', function (): void {
        $this->service->registerIssuer('issuer_123', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC);

        expect(fn () => $this->service->registerIssuer('issuer_123', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC))
            ->toThrow(InvalidArgumentException::class);
    });

    it('validates trust level for issuer type', function (): void {
        // Root CA must have ULTIMATE trust level
        expect(fn () => $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::BASIC))
            ->toThrow(InvalidArgumentException::class);
    });

    it('registers delegated issuers', function (): void {
        $root = $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);

        $delegated = $this->service->registerDelegatedIssuer(
            issuerId: 'delegated_1',
            parentIssuerId: 'root',
            type: IssuerType::ISSUING_CA,
            trustLevel: TrustLevel::HIGH,
        );

        expect($delegated->parentIssuerId)->toBe('root');
        expect($delegated->trustLevel)->toBe(TrustLevel::HIGH);
    });

    it('prevents delegated issuer from exceeding parent trust level', function (): void {
        $this->service->registerIssuer('parent', IssuerType::ISSUING_CA, TrustLevel::VERIFIED);

        expect(fn () => $this->service->registerDelegatedIssuer(
            issuerId: 'child',
            parentIssuerId: 'parent',
            type: IssuerType::TRUSTED_ISSUER,
            trustLevel: TrustLevel::HIGH, // Higher than parent's VERIFIED
        ))->toThrow(InvalidArgumentException::class);
    });

    it('updates trust levels', function (): void {
        $this->service->registerIssuer('issuer_123', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC);

        $updated = $this->service->updateTrustLevel('issuer_123', TrustLevel::VERIFIED);

        expect($updated)->toBeTrue();

        $issuer = $this->service->getIssuer('issuer_123');
        expect($issuer->trustLevel)->toBe(TrustLevel::VERIFIED);

        Event::assertDispatched(TrustLevelChanged::class);
    });

    it('revokes issuers', function (): void {
        $this->service->registerIssuer('issuer_123', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC);

        $revoked = $this->service->revokeIssuer('issuer_123', 'Compliance violation');

        expect($revoked)->toBeTrue();

        $issuer = $this->service->getIssuer('issuer_123');
        expect($issuer->isRevoked())->toBeTrue();
        expect($issuer->revocationReason)->toBe('Compliance violation');
    });

    it('cascades revocation to child issuers', function (): void {
        $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerDelegatedIssuer('child', 'root', IssuerType::ISSUING_CA, TrustLevel::HIGH);

        $this->service->revokeIssuer('root', 'CA compromised');

        $child = $this->service->getIssuer('child');
        expect($child->isRevoked())->toBeTrue();
    });

    it('checks issuer trust status', function (): void {
        $this->service->registerIssuer('issuer_123', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC);

        expect($this->service->isIssuerTrusted('issuer_123'))->toBeTrue();
        expect($this->service->isIssuerTrusted('non_existent'))->toBeFalse();

        $this->service->revokeIssuer('issuer_123', 'Test');
        expect($this->service->isIssuerTrusted('issuer_123'))->toBeFalse();
    });

    it('gets issuer trust level', function (): void {
        $this->service->registerIssuer('issuer_123', IssuerType::TRUSTED_ISSUER, TrustLevel::VERIFIED);

        $level = $this->service->getIssuerTrustLevel('issuer_123');

        expect($level)->toBe(TrustLevel::VERIFIED);
    });

    it('verifies issuer chains', function (): void {
        $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerDelegatedIssuer('intermediate', 'root', IssuerType::INTERMEDIATE_CA, TrustLevel::HIGH);
        $this->service->registerDelegatedIssuer('issuing', 'intermediate', IssuerType::ISSUING_CA, TrustLevel::VERIFIED);

        expect($this->service->verifyIssuerChain('root'))->toBeTrue();
        expect($this->service->verifyIssuerChain('intermediate'))->toBeTrue();
        expect($this->service->verifyIssuerChain('issuing'))->toBeTrue();
    });

    it('fails chain verification for revoked issuer', function (): void {
        $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerDelegatedIssuer('child', 'root', IssuerType::ISSUING_CA, TrustLevel::HIGH);

        $this->service->revokeIssuer('root', 'Test');

        expect($this->service->verifyIssuerChain('child'))->toBeFalse();
    });

    it('gets issuers by trust level', function (): void {
        $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerIssuer('issuer_1', IssuerType::ISSUING_CA, TrustLevel::HIGH);
        $this->service->registerIssuer('issuer_2', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC);

        $highTrust = $this->service->getIssuersByTrustLevel(TrustLevel::HIGH);

        expect($highTrust)->toHaveCount(2); // root and issuer_1
    });

    it('builds trust chains', function (): void {
        $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerDelegatedIssuer('intermediate', 'root', IssuerType::INTERMEDIATE_CA, TrustLevel::HIGH);
        $this->service->registerDelegatedIssuer('issuing', 'intermediate', IssuerType::ISSUING_CA, TrustLevel::VERIFIED);

        $chain = $this->service->buildTrustChain('cred_123', 'issuing');

        expect($chain->isValid())->toBeTrue();
        expect($chain->getDepth())->toBe(3);
        expect($chain->getImmediateIssuer()->issuerId)->toBe('issuing');
        expect($chain->getRootIssuer()->issuerId)->toBe('root');
    });

    it('returns incomplete chain for missing issuer', function (): void {
        $chain = $this->service->buildTrustChain('cred_123', 'non_existent');

        expect($chain->isValid())->toBeFalse();
        expect($chain->validationError)->toContain('not found');
    });

    it('gets child issuers', function (): void {
        $this->service->registerIssuer('root', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerDelegatedIssuer('child_1', 'root', IssuerType::ISSUING_CA, TrustLevel::HIGH);
        $this->service->registerDelegatedIssuer('child_2', 'root', IssuerType::ISSUING_CA, TrustLevel::HIGH);

        $children = $this->service->getChildIssuers('root');

        expect($children)->toHaveCount(2);
    });

    it('gets all issuers', function (): void {
        $this->service->registerIssuer('issuer_1', IssuerType::TRUSTED_ISSUER, TrustLevel::BASIC);
        $this->service->registerIssuer('issuer_2', IssuerType::TRUSTED_ISSUER, TrustLevel::VERIFIED);

        $all = $this->service->getAllIssuers();

        expect($all)->toHaveCount(2);
    });

    it('gets root issuers', function (): void {
        $this->service->registerIssuer('root_1', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerIssuer('root_2', IssuerType::ROOT_CA, TrustLevel::ULTIMATE);
        $this->service->registerDelegatedIssuer('child', 'root_1', IssuerType::ISSUING_CA, TrustLevel::HIGH);

        $roots = $this->service->getRootIssuers();

        expect($roots)->toHaveCount(2);
    });
});
