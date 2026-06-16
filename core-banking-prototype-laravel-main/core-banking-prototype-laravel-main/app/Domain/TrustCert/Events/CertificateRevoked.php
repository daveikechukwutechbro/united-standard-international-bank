<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Events;

use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a certificate is revoked.
 */
class CertificateRevoked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $certificateId,
        public readonly string $reason,
        public readonly DateTimeInterface $revokedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certificate_id' => $this->certificateId,
            'reason'         => $this->reason,
            'revoked_at'     => $this->revokedAt->format('c'),
        ];
    }
}
