<?php

declare(strict_types=1);

namespace App\Domain\Custodian\Events;

use App\Domain\Custodian\Models\CustodianAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountBalanceUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly CustodianAccount $custodianAccount,
        public readonly array $balances,
        public readonly string $timestamp
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
