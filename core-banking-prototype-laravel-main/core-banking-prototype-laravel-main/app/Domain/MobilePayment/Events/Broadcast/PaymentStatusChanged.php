<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Events\Broadcast;

use App\Domain\MobilePayment\Models\PaymentIntent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event for payment status changes.
 *
 * Channel: private-payments.{userId}
 * Event: payment.status_changed
 */
class PaymentStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PaymentIntent $intent,
    ) {
    }

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payments.' . $this->intent->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.status_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $data = [
            'intentId' => $this->intent->public_id,
            'status'   => strtoupper($this->intent->status->value),
            'error'    => null,
        ];

        if ($this->intent->tx_hash) {
            $data['tx'] = [
                'hash'        => $this->intent->tx_hash,
                'explorerUrl' => $this->intent->tx_explorer_url,
            ];
            $data['confirmations'] = $this->intent->confirmations;
            $data['requiredConfirmations'] = $this->intent->required_confirmations;
        }

        if ($this->intent->error_code) {
            $data['error'] = [
                'code'    => $this->intent->error_code,
                'message' => $this->intent->error_message,
            ];
        }

        return $data;
    }
}
