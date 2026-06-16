<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Contracts;

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use App\Domain\CrossChain\ValueObjects\BridgeRoute;

interface BridgeAdapterInterface
{
    /**
     * Get the bridge provider identifier.
     */
    public function getProvider(): BridgeProvider;

    /**
     * Estimate the fee for a bridge transfer.
     *
     * @return array{fee: string, fee_currency: string, estimated_time: int}
     */
    public function estimateFee(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array;

    /**
     * Get a full bridge quote including output amount and route.
     */
    public function getQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote;

    /**
     * Initiate a bridge transfer.
     *
     * @return array{transaction_id: string, status: BridgeStatus, source_tx_hash: ?string}
     */
    public function initiateBridge(
        BridgeQuote $quote,
        string $senderAddress,
        string $recipientAddress,
    ): array;

    /**
     * Get the current status of a bridge transaction.
     *
     * @return array{status: BridgeStatus, source_tx_hash: ?string, dest_tx_hash: ?string, confirmations: int}
     */
    public function getBridgeStatus(string $transactionId): array;

    /**
     * Get supported bridge routes for this adapter.
     *
     * @return array<BridgeRoute>
     */
    public function getSupportedRoutes(): array;

    /**
     * Check if this adapter supports a specific route.
     */
    public function supportsRoute(CrossChainNetwork $source, CrossChainNetwork $dest, string $token): bool;
}
