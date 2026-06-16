<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\Events\CertificateIssued;
use App\Domain\TrustCert\Events\CertificateRevoked;
use App\Domain\TrustCert\Services\CertificateAuthorityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

describe('CertificateAuthorityService', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        Event::fake();
        $this->service = new CertificateAuthorityService('test-ca', 'test-signing-key');
    });

    it('issues certificates', function (): void {
        $validFrom = new DateTimeImmutable('2024-01-01');
        $validUntil = new DateTimeImmutable('2025-01-01');

        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: ['name' => 'Test Subject'],
            validFrom: $validFrom,
            validUntil: $validUntil,
        );

        expect($certificate->subjectId)->toBe('subject_123');
        expect($certificate->subject)->toBe(['name' => 'Test Subject']);
        expect($certificate->status)->toBe(CertificateStatus::ACTIVE);
        expect($certificate->certificateId)->toStartWith('cert_');

        Event::assertDispatched(CertificateIssued::class);
    });

    it('issues child certificates with parent', function (): void {
        $validFrom = new DateTimeImmutable('-1 day');
        $validUntil = new DateTimeImmutable('+1 year');

        $parentCert = $this->service->issueCertificate(
            subjectId: 'parent_subject',
            subject: ['name' => 'Parent'],
            validFrom: $validFrom,
            validUntil: $validUntil,
        );

        $childCert = $this->service->issueCertificate(
            subjectId: 'child_subject',
            subject: ['name' => 'Child'],
            validFrom: $validFrom,
            validUntil: $validUntil,
            parentCertificateId: $parentCert->certificateId,
        );

        expect($childCert->parentCertificateId)->toBe($parentCert->certificateId);
    });

    it('revokes certificates', function (): void {
        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        $result = $this->service->revokeCertificate($certificate->certificateId, 'Key compromise');

        expect($result)->toBeTrue();

        $revoked = $this->service->getCertificate($certificate->certificateId);
        expect($revoked->status)->toBe(CertificateStatus::REVOKED);
        expect($revoked->revocationReason)->toBe('Key compromise');

        Event::assertDispatched(CertificateRevoked::class);
    });

    it('suspends and reinstates certificates', function (): void {
        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        // Suspend
        $suspended = $this->service->suspendCertificate($certificate->certificateId, 'Under review');
        expect($suspended)->toBeTrue();

        $suspendedCert = $this->service->getCertificate($certificate->certificateId);
        expect($suspendedCert->status)->toBe(CertificateStatus::SUSPENDED);

        // Reinstate
        $reinstated = $this->service->reinstateCertificate($certificate->certificateId);
        expect($reinstated)->toBeTrue();

        $reinstatedCert = $this->service->getCertificate($certificate->certificateId);
        expect($reinstatedCert->status)->toBe(CertificateStatus::ACTIVE);
    });

    it('verifies valid certificates', function (): void {
        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        $isValid = $this->service->verifyCertificate($certificate->certificateId);

        expect($isValid)->toBeTrue();
    });

    it('fails verification for revoked certificates', function (): void {
        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        $this->service->revokeCertificate($certificate->certificateId, 'Test');

        $isValid = $this->service->verifyCertificate($certificate->certificateId);

        expect($isValid)->toBeFalse();
    });

    it('gets certificate status', function (): void {
        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        $status = $this->service->getCertificateStatus($certificate->certificateId);

        expect($status)->toBe(CertificateStatus::ACTIVE);
    });

    it('returns null for non-existent certificate', function (): void {
        $certificate = $this->service->getCertificate('non_existent');
        $status = $this->service->getCertificateStatus('non_existent');

        expect($certificate)->toBeNull();
        expect($status)->toBeNull();
    });

    it('gets certificate by subject', function (): void {
        $certificate = $this->service->issueCertificate(
            subjectId: 'subject_123',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        $found = $this->service->getCertificateBySubject('subject_123');

        expect($found)->not->toBeNull();
        expect($found->certificateId)->toBe($certificate->certificateId);
    });

    it('gets active certificates', function (): void {
        // Issue two certificates
        $this->service->issueCertificate(
            subjectId: 'subject_1',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        $cert2 = $this->service->issueCertificate(
            subjectId: 'subject_2',
            subject: [],
            validFrom: new DateTimeImmutable('-1 day'),
            validUntil: new DateTimeImmutable('+1 year'),
        );

        // Revoke one
        $this->service->revokeCertificate($cert2->certificateId, 'Test');

        $active = $this->service->getActiveCertificates();

        expect($active)->toHaveCount(1);
    });

    it('returns CA ID', function (): void {
        expect($this->service->getCaId())->toBe('test-ca');
    });
});
