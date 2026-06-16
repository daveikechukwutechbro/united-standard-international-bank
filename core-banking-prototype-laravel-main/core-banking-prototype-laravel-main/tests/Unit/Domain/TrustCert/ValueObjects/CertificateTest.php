<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\ValueObjects\Certificate;

describe('Certificate Value Object', function (): void {
    it('creates a certificate with all properties', function (): void {
        $validFrom = new DateTimeImmutable('2024-01-01');
        $validUntil = new DateTimeImmutable('2025-01-01');

        $certificate = new Certificate(
            certificateId: 'cert_123',
            subjectId: 'subject_456',
            subject: ['name' => 'Test Subject', 'org' => 'Test Org'],
            publicKey: 'public-key-data',
            signature: 'signature-data',
            validFrom: $validFrom,
            validUntil: $validUntil,
            status: CertificateStatus::ACTIVE,
            parentCertificateId: 'parent_cert_789',
            extensions: ['keyUsage' => 'digitalSignature'],
        );

        expect($certificate->certificateId)->toBe('cert_123');
        expect($certificate->subjectId)->toBe('subject_456');
        expect($certificate->subject)->toBe(['name' => 'Test Subject', 'org' => 'Test Org']);
        expect($certificate->publicKey)->toBe('public-key-data');
        expect($certificate->signature)->toBe('signature-data');
        expect($certificate->status)->toBe(CertificateStatus::ACTIVE);
        expect($certificate->parentCertificateId)->toBe('parent_cert_789');
        expect($certificate->extensions)->toHaveKey('keyUsage');
    });

    it('detects expired certificates', function (): void {
        $validFrom = new DateTimeImmutable('-2 years');
        $validUntil = new DateTimeImmutable('-1 year');

        $certificate = new Certificate(
            certificateId: 'cert_123',
            subjectId: 'subject_456',
            subject: [],
            publicKey: 'key',
            signature: 'sig',
            validFrom: $validFrom,
            validUntil: $validUntil,
            status: CertificateStatus::ACTIVE,
        );

        expect($certificate->isExpired())->toBeTrue();
        expect($certificate->isValid())->toBeFalse();
    });

    it('detects not-yet-valid certificates', function (): void {
        $validFrom = new DateTimeImmutable('+1 year');
        $validUntil = new DateTimeImmutable('+2 years');

        $certificate = new Certificate(
            certificateId: 'cert_123',
            subjectId: 'subject_456',
            subject: [],
            publicKey: 'key',
            signature: 'sig',
            validFrom: $validFrom,
            validUntil: $validUntil,
            status: CertificateStatus::ACTIVE,
        );

        expect($certificate->isNotYetValid())->toBeTrue();
        expect($certificate->isValid())->toBeFalse();
    });

    it('detects valid certificates', function (): void {
        $validFrom = new DateTimeImmutable('-1 year');
        $validUntil = new DateTimeImmutable('+1 year');

        $certificate = new Certificate(
            certificateId: 'cert_123',
            subjectId: 'subject_456',
            subject: [],
            publicKey: 'key',
            signature: 'sig',
            validFrom: $validFrom,
            validUntil: $validUntil,
            status: CertificateStatus::ACTIVE,
        );

        expect($certificate->isValid())->toBeTrue();
        expect($certificate->canSign())->toBeTrue();
    });

    it('identifies root certificates', function (): void {
        $validFrom = new DateTimeImmutable('-1 year');
        $validUntil = new DateTimeImmutable('+1 year');

        $rootCert = new Certificate(
            certificateId: 'root_cert',
            subjectId: 'root_subject',
            subject: [],
            publicKey: 'key',
            signature: 'sig',
            validFrom: $validFrom,
            validUntil: $validUntil,
            status: CertificateStatus::ACTIVE,
            parentCertificateId: null,
        );

        $childCert = new Certificate(
            certificateId: 'child_cert',
            subjectId: 'child_subject',
            subject: [],
            publicKey: 'key',
            signature: 'sig',
            validFrom: $validFrom,
            validUntil: $validUntil,
            status: CertificateStatus::ACTIVE,
            parentCertificateId: 'root_cert',
        );

        expect($rootCert->isRootCertificate())->toBeTrue();
        expect($childCert->isRootCertificate())->toBeFalse();
    });

    it('generates fingerprint', function (): void {
        $certificate = new Certificate(
            certificateId: 'cert_123',
            subjectId: 'subject_456',
            subject: [],
            publicKey: 'key',
            signature: 'sig',
            validFrom: new DateTimeImmutable('2024-01-01'),
            validUntil: new DateTimeImmutable('2025-01-01'),
            status: CertificateStatus::ACTIVE,
        );

        $fingerprint = $certificate->getFingerprint();

        expect($fingerprint)->toBeString();
        expect(strlen($fingerprint))->toBe(64); // SHA-256 hex
    });

    it('converts to array', function (): void {
        $certificate = new Certificate(
            certificateId: 'cert_123',
            subjectId: 'subject_456',
            subject: ['name' => 'Test'],
            publicKey: 'key',
            signature: 'sig',
            validFrom: new DateTimeImmutable('2024-01-01'),
            validUntil: new DateTimeImmutable('2025-01-01'),
            status: CertificateStatus::ACTIVE,
        );

        $array = $certificate->toArray();

        expect($array)->toHaveKey('certificate_id');
        expect($array['certificate_id'])->toBe('cert_123');
        expect($array['status'])->toBe('active');
    });

    it('creates from array', function (): void {
        $data = [
            'certificate_id' => 'cert_123',
            'subject_id'     => 'subject_456',
            'subject'        => ['name' => 'Test'],
            'public_key'     => 'key',
            'signature'      => 'sig',
            'valid_from'     => '2024-01-01T00:00:00+00:00',
            'valid_until'    => '2025-01-01T00:00:00+00:00',
            'status'         => 'active',
        ];

        $certificate = Certificate::fromArray($data);

        expect($certificate->certificateId)->toBe('cert_123');
        expect($certificate->status)->toBe(CertificateStatus::ACTIVE);
    });
});
