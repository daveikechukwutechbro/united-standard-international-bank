<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Events;

use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a certificate expires.
 *
 * This event is emitted by the scheduled expiry check command
 * and can trigger downstream actions like:
 * - Revoking on-chain SBT
 * - Notifying the certificate holder
 * - Updating compliance records
 */
class CertificateExpired
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $certificateId,
        public readonly string $subjectId,
        public readonly DateTimeInterface $expiredAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certificate_id' => $this->certificateId,
            'subject_id'     => $this->subjectId,
            'expired_at'     => $this->expiredAt->format('c'),
        ];
    }
}
