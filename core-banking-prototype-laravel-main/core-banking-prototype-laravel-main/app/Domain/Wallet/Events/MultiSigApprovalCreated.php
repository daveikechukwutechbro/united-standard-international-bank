<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Broadcasting\TenantBroadcastEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new multi-sig approval request is created.
 * This event broadcasts to the tenant channel for real-time notifications.
 */
class MultiSigApprovalCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use TenantBroadcastEvent;

    /**
     * @param  array<string, mixed>  $transactionData
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $approvalRequestId,
        public readonly string $walletId,
        public readonly string $walletName,
        public readonly int $initiatorUserId,
        public readonly int $requiredSignatures,
        public readonly array $transactionData,
        public readonly string $expiresAt,
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
        return 'approval.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'approval_request_id' => $this->approvalRequestId,
            'wallet_id'           => $this->walletId,
            'wallet_name'         => $this->walletName,
            'initiator_user_id'   => $this->initiatorUserId,
            'required_signatures' => $this->requiredSignatures,
            'transaction_data'    => $this->transactionData,
            'expires_at'          => $this->expiresAt,
            'message'             => "New approval request for {$this->walletName}",
        ];
    }
}
