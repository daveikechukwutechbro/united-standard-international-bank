<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Events;

use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a certificate is issued.
 */
class CertificateIssued
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $certificateId,
        public readonly string $subjectId,
        public readonly DateTimeInterface $validFrom,
        public readonly DateTimeInterface $validUntil,
        public readonly ?string $parentCertificateId,
        public readonly DateTimeInterface $issuedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certificate_id'        => $this->certificateId,
            'subject_id'            => $this->subjectId,
            'valid_from'            => $this->validFrom->format('c'),
            'valid_until'           => $this->validUntil->format('c'),
            'parent_certificate_id' => $this->parentCertificateId,
            'issued_at'             => $this->issuedAt->format('c'),
        ];
    }
}
