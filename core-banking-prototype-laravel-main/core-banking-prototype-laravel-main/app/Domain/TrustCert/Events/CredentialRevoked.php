<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Events;

use App\Domain\TrustCert\Enums\RevocationReason;
use DateTimeInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a credential is added to the revocation registry.
 */
class CredentialRevoked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $credentialId,
        public readonly RevocationReason $reason,
        public readonly ?string $revokedBy,
        public readonly DateTimeInterface $revokedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'credential_id' => $this->credentialId,
            'reason'        => $this->reason->value,
            'reason_label'  => $this->reason->label(),
            'revoked_by'    => $this->revokedBy,
            'revoked_at'    => $this->revokedAt->format('c'),
        ];
    }
}
