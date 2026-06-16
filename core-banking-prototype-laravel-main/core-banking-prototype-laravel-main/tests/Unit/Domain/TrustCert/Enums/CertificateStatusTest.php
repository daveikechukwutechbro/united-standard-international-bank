<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\CertificateStatus;

describe('CertificateStatus Enum', function (): void {
    it('has all expected statuses', function (): void {
        $statuses = CertificateStatus::cases();

        expect($statuses)->toHaveCount(5);
        expect(CertificateStatus::PENDING->value)->toBe('pending');
        expect(CertificateStatus::ACTIVE->value)->toBe('active');
        expect(CertificateStatus::SUSPENDED->value)->toBe('suspended');
        expect(CertificateStatus::REVOKED->value)->toBe('revoked');
        expect(CertificateStatus::EXPIRED->value)->toBe('expired');
    });

    it('returns correct labels', function (): void {
        expect(CertificateStatus::PENDING->label())->toBe('Pending');
        expect(CertificateStatus::ACTIVE->label())->toBe('Active');
        expect(CertificateStatus::SUSPENDED->label())->toBe('Suspended');
        expect(CertificateStatus::REVOKED->label())->toBe('Revoked');
        expect(CertificateStatus::EXPIRED->label())->toBe('Expired');
    });

    it('correctly identifies valid status', function (): void {
        expect(CertificateStatus::ACTIVE->isValid())->toBeTrue();
        expect(CertificateStatus::PENDING->isValid())->toBeFalse();
        expect(CertificateStatus::SUSPENDED->isValid())->toBeFalse();
        expect(CertificateStatus::REVOKED->isValid())->toBeFalse();
        expect(CertificateStatus::EXPIRED->isValid())->toBeFalse();
    });

    it('correctly identifies signing capability', function (): void {
        expect(CertificateStatus::ACTIVE->canSign())->toBeTrue();
        expect(CertificateStatus::PENDING->canSign())->toBeFalse();
        expect(CertificateStatus::SUSPENDED->canSign())->toBeFalse();
    });

    it('correctly identifies terminal statuses', function (): void {
        expect(CertificateStatus::REVOKED->isTerminal())->toBeTrue();
        expect(CertificateStatus::EXPIRED->isTerminal())->toBeTrue();
        expect(CertificateStatus::ACTIVE->isTerminal())->toBeFalse();
        expect(CertificateStatus::SUSPENDED->isTerminal())->toBeFalse();
    });

    it('validates state transitions correctly', function (): void {
        // Pending can go to active or revoked
        expect(CertificateStatus::PENDING->canTransitionTo(CertificateStatus::ACTIVE))->toBeTrue();
        expect(CertificateStatus::PENDING->canTransitionTo(CertificateStatus::REVOKED))->toBeTrue();
        expect(CertificateStatus::PENDING->canTransitionTo(CertificateStatus::SUSPENDED))->toBeFalse();

        // Active can go to suspended, revoked, or expired
        expect(CertificateStatus::ACTIVE->canTransitionTo(CertificateStatus::SUSPENDED))->toBeTrue();
        expect(CertificateStatus::ACTIVE->canTransitionTo(CertificateStatus::REVOKED))->toBeTrue();
        expect(CertificateStatus::ACTIVE->canTransitionTo(CertificateStatus::EXPIRED))->toBeTrue();
        expect(CertificateStatus::ACTIVE->canTransitionTo(CertificateStatus::PENDING))->toBeFalse();

        // Suspended can go to active or revoked
        expect(CertificateStatus::SUSPENDED->canTransitionTo(CertificateStatus::ACTIVE))->toBeTrue();
        expect(CertificateStatus::SUSPENDED->canTransitionTo(CertificateStatus::REVOKED))->toBeTrue();

        // Terminal statuses cannot transition
        expect(CertificateStatus::REVOKED->canTransitionTo(CertificateStatus::ACTIVE))->toBeFalse();
        expect(CertificateStatus::EXPIRED->canTransitionTo(CertificateStatus::ACTIVE))->toBeFalse();
    });

    it('returns allowed transitions', function (): void {
        $pendingTransitions = CertificateStatus::PENDING->allowedTransitions();
        expect($pendingTransitions)->toContain(CertificateStatus::ACTIVE);
        expect($pendingTransitions)->toContain(CertificateStatus::REVOKED);

        $revokedTransitions = CertificateStatus::REVOKED->allowedTransitions();
        expect($revokedTransitions)->toBeEmpty();
    });
});
