<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Contracts;

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\ValueObjects\Certificate;
use DateTimeInterface;

/**
 * Contract for Certificate Authority operations.
 */
interface CertificateAuthorityInterface
{
    /**
     * Issue a new certificate.
     *
     * @param array<string, mixed> $subject
     * @param array<string, mixed> $extensions
     */
    public function issueCertificate(
        string $subjectId,
        array $subject,
        DateTimeInterface $validFrom,
        DateTimeInterface $validUntil,
        ?string $parentCertificateId = null,
        array $extensions = [],
    ): Certificate;

    /**
     * Revoke a certificate.
     */
    public function revokeCertificate(string $certificateId, string $reason): bool;

    /**
     * Suspend a certificate temporarily.
     */
    public function suspendCertificate(string $certificateId, string $reason): bool;

    /**
     * Reinstate a suspended certificate.
     */
    public function reinstateCertificate(string $certificateId): bool;

    /**
     * Get certificate by ID.
     */
    public function getCertificate(string $certificateId): ?Certificate;

    /**
     * Verify a certificate is valid.
     */
    public function verifyCertificate(string $certificateId): bool;

    /**
     * Get certificate status.
     */
    public function getCertificateStatus(string $certificateId): ?CertificateStatus;
}
