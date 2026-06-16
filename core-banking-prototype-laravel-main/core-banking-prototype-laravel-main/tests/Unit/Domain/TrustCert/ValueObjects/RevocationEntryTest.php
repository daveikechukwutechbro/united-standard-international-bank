<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\RevocationReason;
use App\Domain\TrustCert\ValueObjects\RevocationEntry;

describe('RevocationEntry Value Object', function (): void {
    it('creates a revocation entry', function (): void {
        $revokedAt = new DateTimeImmutable('2024-06-15');

        $entry = new RevocationEntry(
            entryId: 'rev_123',
            credentialId: 'cred_456',
            reason: RevocationReason::KEY_COMPROMISE,
            revokedAt: $revokedAt,
            issuerId: 'issuer_789',
            revokedBy: 'admin_user',
            notes: 'Compromised key detected',
        );

        expect($entry->entryId)->toBe('rev_123');
        expect($entry->credentialId)->toBe('cred_456');
        expect($entry->reason)->toBe(RevocationReason::KEY_COMPROMISE);
        expect($entry->issuerId)->toBe('issuer_789');
        expect($entry->revokedBy)->toBe('admin_user');
        expect($entry->notes)->toBe('Compromised key detected');
    });

    it('identifies permanent revocations', function (): void {
        $permanentEntry = new RevocationEntry(
            entryId: 'rev_1',
            credentialId: 'cred_1',
            reason: RevocationReason::KEY_COMPROMISE,
            revokedAt: new DateTimeImmutable(),
        );

        $holdEntry = new RevocationEntry(
            entryId: 'rev_2',
            credentialId: 'cred_2',
            reason: RevocationReason::CERTIFICATE_HOLD,
            revokedAt: new DateTimeImmutable(),
        );

        expect($permanentEntry->isPermanent())->toBeTrue();
        expect($permanentEntry->isHold())->toBeFalse();
        expect($holdEntry->isPermanent())->toBeFalse();
        expect($holdEntry->isHold())->toBeTrue();
    });

    it('calculates seconds since revocation', function (): void {
        $entry = new RevocationEntry(
            entryId: 'rev_123',
            credentialId: 'cred_456',
            reason: RevocationReason::SUPERSEDED,
            revokedAt: new DateTimeImmutable('-1 hour'),
        );

        $seconds = $entry->getSecondsSinceRevocation();

        expect($seconds)->toBeGreaterThan(3500);
        expect($seconds)->toBeLessThan(3700);
    });

    it('generates hash', function (): void {
        $entry = new RevocationEntry(
            entryId: 'rev_123',
            credentialId: 'cred_456',
            reason: RevocationReason::SUPERSEDED,
            revokedAt: new DateTimeImmutable('2024-01-01'),
        );

        $hash = $entry->getHash();

        expect($hash)->toBeString();
        expect(strlen($hash))->toBe(64);
    });

    it('converts to array', function (): void {
        $entry = new RevocationEntry(
            entryId: 'rev_123',
            credentialId: 'cred_456',
            reason: RevocationReason::KEY_COMPROMISE,
            revokedAt: new DateTimeImmutable('2024-01-01'),
        );

        $array = $entry->toArray();

        expect($array)->toHaveKey('entry_id');
        expect($array)->toHaveKey('reason');
        expect($array)->toHaveKey('reason_label');
        expect($array)->toHaveKey('is_permanent');
        expect($array['reason'])->toBe('key_compromise');
        expect($array['is_permanent'])->toBeTrue();
    });

    it('creates from array', function (): void {
        $data = [
            'entry_id'      => 'rev_123',
            'credential_id' => 'cred_456',
            'reason'        => 'key_compromise',
            'revoked_at'    => '2024-01-01T00:00:00+00:00',
        ];

        $entry = RevocationEntry::fromArray($data);

        expect($entry->entryId)->toBe('rev_123');
        expect($entry->reason)->toBe(RevocationReason::KEY_COMPROMISE);
    });
});
