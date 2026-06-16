<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\RevocationReason;
use App\Domain\TrustCert\Events\CredentialRevoked;
use App\Domain\TrustCert\Services\RevocationRegistryService;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

describe('RevocationRegistryService', function (): void {
    beforeEach(function (): void {
        Event::fake();
        $this->service = new RevocationRegistryService();
    });

    it('revokes credentials', function (): void {
        $entry = $this->service->revoke('cred_123', RevocationReason::KEY_COMPROMISE);

        expect($entry->credentialId)->toBe('cred_123');
        expect($entry->reason)->toBe(RevocationReason::KEY_COMPROMISE);
        expect($entry->entryId)->toStartWith('rev_');

        Event::assertDispatched(CredentialRevoked::class);
    });

    it('revokes with issuer tracking', function (): void {
        $entry = $this->service->revokeWithIssuer(
            credentialId: 'cred_123',
            issuerId: 'issuer_456',
            reason: RevocationReason::SUPERSEDED,
            revokedBy: 'admin',
            notes: 'Replaced with new credential',
        );

        expect($entry->issuerId)->toBe('issuer_456');
        expect($entry->revokedBy)->toBe('admin');
        expect($entry->notes)->toBe('Replaced with new credential');
    });

    it('checks revocation status', function (): void {
        expect($this->service->isRevoked('cred_123'))->toBeFalse();

        $this->service->revoke('cred_123', RevocationReason::KEY_COMPROMISE);

        expect($this->service->isRevoked('cred_123'))->toBeTrue();
    });

    it('gets revocation entry', function (): void {
        $this->service->revoke('cred_123', RevocationReason::KEY_COMPROMISE);

        $entry = $this->service->getRevocationEntry('cred_123');

        expect($entry)->not->toBeNull();
        expect($entry->credentialId)->toBe('cred_123');
    });

    it('returns null for non-revoked credential', function (): void {
        $entry = $this->service->getRevocationEntry('non_existent');

        expect($entry)->toBeNull();
    });

    it('prevents duplicate revocations', function (): void {
        $this->service->revoke('cred_123', RevocationReason::KEY_COMPROMISE);

        expect(fn () => $this->service->revoke('cred_123', RevocationReason::SUPERSEDED))
            ->toThrow(InvalidArgumentException::class);
    });

    it('gets revocations by issuer', function (): void {
        $this->service->revokeWithIssuer('cred_1', 'issuer_A', RevocationReason::KEY_COMPROMISE);
        $this->service->revokeWithIssuer('cred_2', 'issuer_A', RevocationReason::SUPERSEDED);
        $this->service->revokeWithIssuer('cred_3', 'issuer_B', RevocationReason::KEY_COMPROMISE);

        $issuerARevocations = $this->service->getRevocationsByIssuer('issuer_A');

        expect($issuerARevocations)->toHaveCount(2);
    });

    it('gets revocations since timestamp', function (): void {
        $this->service->revoke('cred_1', RevocationReason::KEY_COMPROMISE);

        $since = new DateTimeImmutable('-1 minute');
        $revocations = $this->service->getRevocationsSince($since);

        expect($revocations)->toHaveCount(1);
    });

    it('counts revocations', function (): void {
        expect($this->service->getRevocationCount())->toBe(0);

        $this->service->revoke('cred_1', RevocationReason::KEY_COMPROMISE);
        $this->service->revoke('cred_2', RevocationReason::SUPERSEDED);

        expect($this->service->getRevocationCount())->toBe(2);
    });

    it('generates revocation list hash', function (): void {
        $this->service->revoke('cred_1', RevocationReason::KEY_COMPROMISE);

        $hash = $this->service->generateRevocationListHash();

        expect($hash)->toBeString();
        expect(strlen($hash))->toBe(64);
    });

    it('removes certificate holds', function (): void {
        $this->service->revoke('cred_123', RevocationReason::CERTIFICATE_HOLD);

        expect($this->service->isRevoked('cred_123'))->toBeTrue();

        $removed = $this->service->removeHold('cred_123');

        expect($removed)->toBeTrue();
        expect($this->service->isRevoked('cred_123'))->toBeFalse();
    });

    it('cannot remove permanent revocations', function (): void {
        $this->service->revoke('cred_123', RevocationReason::KEY_COMPROMISE);

        $removed = $this->service->removeHold('cred_123');

        expect($removed)->toBeFalse();
        expect($this->service->isRevoked('cred_123'))->toBeTrue();
    });

    it('checks batch of credentials', function (): void {
        $this->service->revoke('cred_1', RevocationReason::KEY_COMPROMISE);
        $this->service->revoke('cred_3', RevocationReason::SUPERSEDED);

        $results = $this->service->checkBatch(['cred_1', 'cred_2', 'cred_3']);

        expect($results)->toBe([
            'cred_1' => true,
            'cred_2' => false,
            'cred_3' => true,
        ]);
    });

    it('gets revocations by reason', function (): void {
        $this->service->revoke('cred_1', RevocationReason::KEY_COMPROMISE);
        $this->service->revoke('cred_2', RevocationReason::SUPERSEDED);
        $this->service->revoke('cred_3', RevocationReason::KEY_COMPROMISE);

        $keyCompromises = $this->service->getRevocationsByReason(RevocationReason::KEY_COMPROMISE);

        expect($keyCompromises)->toHaveCount(2);
    });

    it('generates StatusList2021 format', function (): void {
        $this->service->revoke('cred_1', RevocationReason::KEY_COMPROMISE);

        $statusList = $this->service->toStatusList2021();

        expect($statusList)->toHaveKey('@context');
        expect($statusList)->toHaveKey('type');
        expect($statusList)->toHaveKey('credentialSubject');
        expect($statusList['type'])->toContain('StatusList2021Credential');
    });
});
