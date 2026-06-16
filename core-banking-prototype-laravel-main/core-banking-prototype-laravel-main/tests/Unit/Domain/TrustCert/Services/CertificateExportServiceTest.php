<?php

declare(strict_types=1);

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\Services\CertificateAuthorityService;
use App\Domain\TrustCert\Services\CertificateExportService;
use App\Domain\TrustCert\Services\PresentationService;
use App\Domain\TrustCert\ValueObjects\Certificate;

describe('CertificateExportService', function (): void {
    it('can be instantiated with dependencies', function (): void {
        $caService = Mockery::mock(CertificateAuthorityService::class);
        $presentationService = Mockery::mock(PresentationService::class);

        $service = new CertificateExportService($caService, $presentationService);

        expect($service)->toBeInstanceOf(CertificateExportService::class);
    });

    it('returns null for non-existent certificate details', function (): void {
        $caService = Mockery::mock(CertificateAuthorityService::class);
        $caService->shouldReceive('getCertificate')
            ->with('cert_nonexistent')
            ->andReturn(null);

        $presentationService = Mockery::mock(PresentationService::class);

        $service = new CertificateExportService($caService, $presentationService);
        $details = $service->getCertificateDetails('cert_nonexistent');

        expect($details)->toBeNull();
    });

    it('returns formatted certificate details matching mobile spec', function (): void {
        $certificate = Certificate::fromArray([
            'certificate_id'        => 'cert_test123',
            'subject_id'            => 'user_456',
            'subject'               => ['name' => 'Test User', 'type' => 'individual'],
            'public_key'            => base64_encode('test-public-key'),
            'signature'             => 'test-signature',
            'valid_from'            => '2026-01-01T00:00:00Z',
            'valid_until'           => '2027-01-01T00:00:00Z',
            'status'                => CertificateStatus::ACTIVE->value,
            'parent_certificate_id' => null,
            'extensions'            => ['usage' => 'identity'],
        ]);

        $caService = Mockery::mock(CertificateAuthorityService::class);
        $caService->shouldReceive('getCertificate')
            ->with('cert_test123')
            ->andReturn($certificate);

        $presentationService = Mockery::mock(PresentationService::class);

        $service = new CertificateExportService($caService, $presentationService);
        $details = $service->getCertificateDetails('cert_test123');

        expect($details)->not->toBeNull();
        expect($details['certId'])->toBe('cert_test123');
        expect($details['status'])->toBe('verified');
        expect($details['verificationStatus'])->toBe('Fully Verified');
        expect($details['scope'])->toBe('Individual Global Account');
        expect($details['disclaimer'])->toContain('FinAegis');
        expect($details)->toHaveKeys([
            'certId', 'status', 'verificationStatus', 'identityId',
            'scope', 'validUntil', 'issuedAt', 'disclaimer', 'qrPayload',
        ]);
    });

    it('returns null PDF for non-existent certificate', function (): void {
        $caService = Mockery::mock(CertificateAuthorityService::class);
        $caService->shouldReceive('getCertificate')
            ->with('cert_missing')
            ->andReturn(null);

        $presentationService = Mockery::mock(PresentationService::class);

        $service = new CertificateExportService($caService, $presentationService);
        $pdf = $service->exportToPdf('cert_missing');

        expect($pdf)->toBeNull();
    });

    it('includes FinAegis branding in disclaimer', function (): void {
        $certificate = Certificate::fromArray([
            'certificate_id'        => 'cert_brand',
            'subject_id'            => 'user_brand',
            'subject'               => ['name' => 'Brand Test'],
            'public_key'            => base64_encode('pk'),
            'signature'             => 'sig',
            'valid_from'            => '2026-01-01T00:00:00Z',
            'valid_until'           => '2027-01-01T00:00:00Z',
            'status'                => CertificateStatus::ACTIVE->value,
            'parent_certificate_id' => null,
            'extensions'            => [],
        ]);

        $caService = Mockery::mock(CertificateAuthorityService::class);
        $caService->shouldReceive('getCertificate')
            ->with('cert_brand')
            ->andReturn($certificate);

        $presentationService = Mockery::mock(PresentationService::class);

        $service = new CertificateExportService($caService, $presentationService);
        $details = $service->getCertificateDetails('cert_brand');

        expect($details['disclaimer'])->toContain('FinAegis ecosystem');
        expect($details['disclaimer'])->not->toContain('ShieldPay');
        expect($details['qrPayload'])->toContain('cert_brand');
    });
});
