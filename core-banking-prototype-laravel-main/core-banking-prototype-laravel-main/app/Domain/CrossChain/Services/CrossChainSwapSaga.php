<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Exceptions\BridgeTransactionFailedException;
use App\Domain\CrossChain\ValueObjects\CrossChainSwapQuote;
use App\Domain\DeFi\Services\SwapRouterService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Saga orchestrator for cross-chain swap compensation.
 *
 * If the swap fails after a successful bridge, triggers appropriate
 * compensation (refund tracking, position recovery).
 */
class CrossChainSwapSaga
{
    public function __construct(
        private readonly BridgeOrchestratorService $bridgeOrchestrator,
        private readonly SwapRouterService $swapRouter,
        private readonly BridgeTransactionTracker $transactionTracker,
    ) {
    }

    /**
     * Execute the bridge step of a cross-chain swap.
     *
     * @return array{transaction_id: string, status: BridgeStatus}
     *
     * @throws BridgeTransactionFailedException
     */
    public function executeBridge(
        CrossChainSwapQuote $quote,
        string $walletAddress,
        string $userUuid,
    ): array {
        try {
            $result = $this->bridgeOrchestrator->initiateBridge(
                $quote->bridgeQuote->quoteId,
                $walletAddress,
                $walletAddress,
                $userUuid,
            );

            $this->transactionTracker->recordTransaction(
                $result['transaction_id'],
                $quote->sourceChain,
                $quote->destChain,
                $quote->bridgeQuote->route->token,
                $quote->bridgeQuote->inputAmount,
                $quote->bridgeQuote->getProvider(),
                $walletAddress,
                $walletAddress,
            );

            return $result;
        } catch (BridgeTransactionFailedException $e) {
            Log::error('Cross-chain swap: bridge failed', [
                'quote_id' => $quote->quoteId,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute the swap step after a successful bridge.
     *
     * On failure, logs the compensation needed (tokens remain on dest chain).
     *
     * @return array{tx_hash: string, output_amount: string, swap_failed?: bool}
     */
    public function executeSwapAfterBridge(
        CrossChainSwapQuote $quote,
        string $walletAddress,
        array $bridgeResult,
    ): array {
        try {
            $result = $this->swapRouter->executeSwap(
                $quote->swapQuote,
                $walletAddress,
            );

            return [
                'tx_hash'       => $result['tx_hash'],
                'output_amount' => $result['output_amount'],
            ];
        } catch (Throwable $e) {
            Log::error('Cross-chain swap: swap failed after bridge', [
                'quote_id'  => $quote->quoteId,
                'bridge_tx' => $bridgeResult['transaction_id'],
                'error'     => $e->getMessage(),
                'recovery'  => 'Tokens remain as input token on destination chain',
            ]);

            // Compensation: return bridged amount as output (tokens on dest chain)
            return [
                'tx_hash'       => 'failed_swap_' . $bridgeResult['transaction_id'],
                'output_amount' => $quote->bridgeQuote->outputAmount,
                'swap_failed'   => true,
            ];
        }
    }

    /**
     * Check the current status of a cross-chain swap's bridge step.
     *
     * @return array{status: string, bridge_status: BridgeStatus}
     */
    public function checkSwapStatus(string $transactionId): array
    {
        $tracked = $this->transactionTracker->getTransaction($transactionId);

        if ($tracked === null) {
            return [
                'status'        => 'unknown',
                'bridge_status' => BridgeStatus::FAILED,
            ];
        }

        return [
            'status'        => $tracked['status'],
            'bridge_status' => BridgeStatus::from($tracked['status']),
        ];
    }
}
