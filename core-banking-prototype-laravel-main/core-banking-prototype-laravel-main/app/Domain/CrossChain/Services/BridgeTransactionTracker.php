<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks bridge transaction lifecycle: initiated -> bridging -> confirming -> completed/failed.
 */
class BridgeTransactionTracker
{
    private const CACHE_PREFIX = 'bridge_tx:';

    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Record a new bridge transaction.
     *
     * @param array<string, mixed> $metadata
     */
    public function recordTransaction(
        string $transactionId,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
        BridgeProvider $provider,
        string $senderAddress,
        string $recipientAddress,
        array $metadata = [],
    ): void {
        $data = [
            'transaction_id'    => $transactionId,
            'source_chain'      => $sourceChain->value,
            'dest_chain'        => $destChain->value,
            'token'             => $token,
            'amount'            => $amount,
            'provider'          => $provider->value,
            'sender_address'    => $senderAddress,
            'recipient_address' => $recipientAddress,
            'status'            => BridgeStatus::INITIATED->value,
            'source_tx_hash'    => null,
            'dest_tx_hash'      => null,
            'initiated_at'      => now()->toIso8601String(),
            'completed_at'      => null,
            'metadata'          => $metadata,
        ];

        Cache::put(self::CACHE_PREFIX . $transactionId, $data, self::CACHE_TTL);

        $this->addToUserTransactions($senderAddress, $transactionId);
    }

    /**
     * Update the status of a bridge transaction.
     */
    public function updateStatus(
        string $transactionId,
        BridgeStatus $status,
        ?string $sourceTxHash = null,
        ?string $destTxHash = null,
    ): void {
        $data = $this->getTransaction($transactionId);

        if ($data === null) {
            return;
        }

        $data['status'] = $status->value;

        if ($sourceTxHash !== null) {
            $data['source_tx_hash'] = $sourceTxHash;
        }

        if ($destTxHash !== null) {
            $data['dest_tx_hash'] = $destTxHash;
        }

        if ($status->isTerminal()) {
            $data['completed_at'] = now()->toIso8601String();
        }

        Cache::put(self::CACHE_PREFIX . $transactionId, $data, self::CACHE_TTL);
    }

    /**
     * Get a transaction by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getTransaction(string $transactionId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $transactionId);
    }

    /**
     * Get all transactions for a user address.
     *
     * @return array<array<string, mixed>>
     */
    public function getUserTransactions(string $userAddress, int $limit = 50): array
    {
        $txIds = Cache::get(self::CACHE_PREFIX . 'user:' . $userAddress, []);
        $transactions = [];

        foreach (array_slice(array_reverse($txIds), 0, $limit) as $txId) {
            $tx = $this->getTransaction($txId);
            if ($tx !== null) {
                $transactions[] = $tx;
            }
        }

        return $transactions;
    }

    /**
     * Get pending transactions (not yet terminal).
     *
     * @return array<array<string, mixed>>
     */
    public function getPendingTransactions(string $userAddress): array
    {
        return array_filter(
            $this->getUserTransactions($userAddress),
            fn (array $tx) => BridgeStatus::from($tx['status'])->isPending(),
        );
    }

    /**
     * Get transaction count by status.
     *
     * @return array<string, int>
     */
    public function getTransactionStats(string $userAddress): array
    {
        $transactions = $this->getUserTransactions($userAddress, 1000);
        $stats = [];

        foreach (BridgeStatus::cases() as $status) {
            $stats[$status->value] = 0;
        }

        foreach ($transactions as $tx) {
            $stats[$tx['status']]++;
        }

        return $stats;
    }

    private function addToUserTransactions(string $userAddress, string $transactionId): void
    {
        $key = self::CACHE_PREFIX . 'user:' . $userAddress;
        $txIds = Cache::get($key, []);
        $txIds[] = $transactionId;

        Cache::put($key, $txIds, self::CACHE_TTL);
    }
}
