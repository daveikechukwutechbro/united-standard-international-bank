<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Listeners;

use App\Domain\Account\Models\BlockchainAddress;
use App\Domain\Wallet\Services\HeliusWebhookSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Automatically registers new Solana addresses with Helius webhook.
 *
 * Listens for BlockchainAddress model creation events and adds
 * Solana addresses to the Helius webhook for real-time monitoring.
 */
class SyncSolanaAddressToHelius implements ShouldQueue
{
    public string $queue = 'default';

    public function __construct(
        private readonly HeliusWebhookSyncService $heliusSync,
    ) {
    }

    /**
     * Handle the blockchain address creation.
     */
    public function handle(object $event): void
    {
        // Support both Eloquent events and custom events
        $address = $this->resolveAddress($event);

        if ($address === null) {
            return;
        }

        if ($address->chain !== 'solana') {
            return;
        }

        if (! $address->is_active) {
            return;
        }

        $result = $this->heliusSync->addAddress($address->address);

        if ($result) {
            Log::info('Helius: Solana address registered for monitoring', [
                'address' => $address->address,
                'user'    => $address->user_uuid,
            ]);
        }
    }

    private function resolveAddress(object $event): ?BlockchainAddress
    {
        // Eloquent model event
        if ($event instanceof BlockchainAddress) {
            return $event;
        }

        // Custom event with model property
        if (property_exists($event, 'blockchainAddress') && $event->blockchainAddress instanceof BlockchainAddress) {
            return $event->blockchainAddress;
        }

        // Custom event with address property
        if (property_exists($event, 'model') && $event->model instanceof BlockchainAddress) {
            return $event->model;
        }

        return null;
    }
}
