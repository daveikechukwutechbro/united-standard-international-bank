<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\RevocationReason;

describe('RevocationReason Enum', function (): void {
    it('has all expected reasons', function (): void {
        $reasons = RevocationReason::cases();

        expect($reasons)->toHaveCount(9);
        expect(RevocationReason::UNSPECIFIED->value)->toBe('unspecified');
        expect(RevocationReason::KEY_COMPROMISE->value)->toBe('key_compromise');
        expect(RevocationReason::CA_COMPROMISE->value)->toBe('ca_compromise');
        expect(RevocationReason::SUPERSEDED->value)->toBe('superseded');
        expect(RevocationReason::CERTIFICATE_HOLD->value)->toBe('certificate_hold');
    });

    it('returns correct labels', function (): void {
        expect(RevocationReason::UNSPECIFIED->label())->toBe('Unspecified');
        expect(RevocationReason::KEY_COMPROMISE->label())->toBe('Key Compromise');
        expect(RevocationReason::CA_COMPROMISE->label())->toBe('CA Compromise');
        expect(RevocationReason::CERTIFICATE_HOLD->label())->toBe('Certificate Hold');
    });

    it('returns descriptions', function (): void {
        expect(RevocationReason::KEY_COMPROMISE->description())->toContain('compromised');
        expect(RevocationReason::SUPERSEDED->description())->toContain('superseded');
    });

    it('returns RFC codes', function (): void {
        expect(RevocationReason::UNSPECIFIED->rfcCode())->toBe(0);
        expect(RevocationReason::KEY_COMPROMISE->rfcCode())->toBe(1);
        expect(RevocationReason::CA_COMPROMISE->rfcCode())->toBe(2);
        expect(RevocationReason::CERTIFICATE_HOLD->rfcCode())->toBe(6);
    });

    it('correctly identifies permanent revocations', function (): void {
        expect(RevocationReason::KEY_COMPROMISE->isPermanent())->toBeTrue();
        expect(RevocationReason::SUPERSEDED->isPermanent())->toBeTrue();
        expect(RevocationReason::CERTIFICATE_HOLD->isPermanent())->toBeFalse();
    });
});
