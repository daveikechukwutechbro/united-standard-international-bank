<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Broadcasting\TenantBroadcastEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a multi-sig approval is completed (transaction broadcast).
 * This event broadcasts to the tenant channel for real-time notifications.
 */
class MultiSigApprovalCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;
    use TenantBroadcastEvent;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $approvalRequestId,
        public readonly string $walletId,
        public readonly string $walletName,
        public readonly string $transactionHash,
        public readonly string $status,
        public readonly ?string $errorMessage = null,
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
        return 'approval.completed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $isSuccess = $this->status === 'completed';

        return [
            'approval_request_id' => $this->approvalRequestId,
            'wallet_id'           => $this->walletId,
            'wallet_name'         => $this->walletName,
            'transaction_hash'    => $this->transactionHash,
            'status'              => $this->status,
            'error_message'       => $this->errorMessage,
            'is_success'          => $isSuccess,
            'message'             => $isSuccess
                ? "Transaction from {$this->walletName} completed successfully!"
                : "Transaction from {$this->walletName} failed: {$this->errorMessage}",
        ];
    }
}
