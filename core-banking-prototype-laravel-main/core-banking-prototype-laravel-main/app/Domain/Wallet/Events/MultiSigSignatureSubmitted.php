<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Broadcasting\TenantBroadcastEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a signature is submitted for a multi-sig approval request.
 * This event broadcasts to the tenant channel for real-time notifications.
 */
class MultiSigSignatureSubmitted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use TenantBroadcastEvent;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $approvalRequestId,
        public readonly string $walletId,
        public readonly string $signerId,
        public readonly string $signerName,
        public readonly int $userId,
        public readonly int $currentSignatures,
        public readonly int $requiredSignatures,
        public readonly bool $quorumReached,
    ) {
    }

    /**
     * Get the channel suffix for this event.
     */
    protected function tenantChannelSuffix(): string
    {
        return 'wallet.multi-sig';
    }

    /**
     * Get the event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'signature.submitted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'approval_request_id'  => $this->approvalRequestId,
            'wallet_id'            => $this->walletId,
            'signer_id'            => $this->signerId,
            'signer_name'          => $this->signerName,
            'user_id'              => $this->userId,
            'current_signatures'   => $this->currentSignatures,
            'required_signatures'  => $this->requiredSignatures,
            'remaining_signatures' => max(0, $this->requiredSignatures - $this->currentSignatures),
            'quorum_reached'       => $this->quorumReached,
            'message'              => $this->quorumReached
                ? 'All required signatures collected!'
                : "{$this->signerName} signed ({$this->currentSignatures}/{$this->requiredSignatures})",
        ];
    }
}
