<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Service for exporting TrustCert certificates to PDF.
 *
 * Generates formatted PDF documents with certificate details,
 * trust chain info, and verification QR data.
 */
class CertificateExportService
{
    public function __construct(
        private readonly CertificateAuthorityService $caService,
        private readonly PresentationService $presentationService,
    ) {
    }

    /**
     * Get certificate details for API response.
     *
     * @return array<string, mixed>|null
     */
    public function getCertificateDetails(string $certificateId): ?array
    {
        $certificate = $this->caService->getCertificate($certificateId);

        if (! $certificate) {
            return null;
        }

        $subjectName = $certificate->subject['name'] ?? 'Unknown';
        $subjectType = $certificate->subject['type'] ?? 'individual';

        return [
            'certId'             => $certificate->certificateId,
            'status'             => $certificate->isValid() ? 'verified' : $certificate->status->value,
            'verificationStatus' => $certificate->isValid() ? 'Fully Verified' : ucfirst($certificate->status->value),
            'identityId'         => strtoupper(substr(hash('sha256', $certificate->subjectId), 0, 4)) . '-'
                                  . strtoupper(substr(hash('sha256', $certificate->certificateId), 0, 4)) . '-'
                                  . strtoupper(substr($certificate->subjectId, -1)),
            'scope'      => $subjectType === 'individual' ? 'Individual Global Account' : 'Organization Account',
            'validUntil' => $certificate->validUntil->format('Y-m-d'),
            'issuedAt'   => $certificate->validFrom->format('Y-m-d'),
            'disclaimer' => 'This certificate confirms identity trust within the FinAegis ecosystem. Authenticity is cryptographically signed and can be verified via the official verification portal.',
            'qrPayload'  => 'https://trust.finaegis.com/verify/' . $certificate->certificateId,
        ];
    }

    /**
     * Export a certificate to PDF. Stores the file and returns metadata.
     *
     * @return array{pdfUrl: string, expiresAt: string}|null
     */
    public function exportToPdf(string $certificateId): ?array
    {
        $certificate = $this->caService->getCertificate($certificateId);

        if (! $certificate) {
            return null;
        }

        try {
            // Generate a presentation token for the QR code
            $presentation = $this->presentationService->generatePresentation(
                certificateId: $certificateId,
                requestedClaims: ['certificate_type', 'status', 'valid_from', 'valid_until', 'subject_id'],
                validityMinutes: 60,
            );

            $data = [
                'certificate'     => $certificate,
                'verificationUrl' => $presentation['verification_url'] ?? null,
                'qrData'          => $presentation['qr_code_data'] ?? null,
                'deepLink'        => $presentation['deep_link'] ?? null,
                'generatedAt'     => now()->toIso8601String(),
                'disclaimer'      => 'This certificate is issued within the FinAegis ecosystem. It is intended for identity and compliance verification within FinAegis services only.',
                'branding'        => [
                    'name'   => 'FinAegis',
                    'shield' => 'Aegis Shield',
                ],
            ];

            /** @var \Barryvdh\DomPDF\PDF $pdf */
            $pdf = Pdf::loadView('trustcert.certificate-pdf', $data);
            $pdf->setPaper('a4', 'portrait');

            $pdfContent = $pdf->output();

            // Store PDF and return URL
            $filename = 'certs/' . Str::random(32) . '.pdf';
            Storage::disk('public')->put($filename, $pdfContent);

            $expiresAt = now()->addHour();

            return [
                'pdfUrl'    => Storage::disk('public')->url($filename),
                'expiresAt' => $expiresAt->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::error('Certificate PDF export failed', [
                'certificate_id' => $certificateId,
                'error'          => $e->getMessage(),
            ]);

            return null;
        }
    }
}
