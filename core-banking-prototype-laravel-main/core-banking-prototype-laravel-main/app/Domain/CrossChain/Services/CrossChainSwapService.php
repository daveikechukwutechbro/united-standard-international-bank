<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Events\CrossChainSwapCompleted;
use App\Domain\CrossChain\Events\CrossChainSwapInitiated;
use App\Domain\CrossChain\Exceptions\BridgeTransactionFailedException;
use App\Domain\CrossChain\ValueObjects\CrossChainSwapQuote;
use App\Domain\DeFi\Services\SwapRouterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates cross-chain swaps: bridge + swap in optimal order.
 *
 * Strategy: bridge first (source → dest), then swap on destination chain.
 * Uses CrossChainSwapSaga for compensation on failure.
 */
class CrossChainSwapService
{
    public function __construct(
        private readonly BridgeOrchestratorService $bridgeOrchestrator,
        private readonly SwapRouterService $swapRouter,
        private readonly CrossChainSwapSaga $saga,
    ) {
    }

    /**
     * Get a cross-chain swap quote.
     *
     * If input and output tokens are the same, only bridge is needed.
     * Otherwise, bridge the input token, then swap on the destination chain.
     */
    public function getQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $inputToken,
        string $outputToken,
        string $amount,
    ): CrossChainSwapQuote {
        // Step 1: Get bridge quote for the input token
        $bridgeQuote = $this->bridgeOrchestrator->getBestQuote(
            $sourceChain,
            $destChain,
            $inputToken,
            $amount,
        );

        // Step 2: If tokens differ, get swap quote on destination chain
        $swapQuote = null;
        $estimatedOutput = $bridgeQuote->outputAmount;

        if ($inputToken !== $outputToken) {
            $swapQuote = $this->swapRouter->findBestRoute(
                $destChain,
                $inputToken,
                $outputToken,
                $bridgeQuote->outputAmount,
            );
            $estimatedOutput = $swapQuote->outputAmount;
        }

        // Total fee = bridge fee + swap gas estimate
        $totalFee = $bridgeQuote->fee;
        if ($swapQuote !== null) {
            $totalFee = bcadd($totalFee, $swapQuote->gasEstimate, 8);
        }

        $estimatedTime = $bridgeQuote->estimatedTimeSeconds;
        if ($swapQuote !== null) {
            $estimatedTime += 30; // Additional time for swap execution
        }

        return new CrossChainSwapQuote(
            quoteId: 'xswap_' . Str::random(32),
            sourceChain: $sourceChain,
            destChain: $destChain,
            inputToken: $inputToken,
            outputToken: $outputToken,
            inputAmount: $amount,
            estimatedOutputAmount: $estimatedOutput,
            bridgeQuote: $bridgeQuote,
            swapQuote: $swapQuote,
            totalFee: $totalFee,
            feeCurrency: $bridgeQuote->feeCurrency,
            estimatedTimeSeconds: $estimatedTime,
        );
    }

    /**
     * Execute a cross-chain swap using a previously obtained quote.
     *
     * @return array{bridge_tx: string, swap_tx: ?string, output_amount: string, status: string}
     *
     * @throws BridgeTransactionFailedException
     */
    public function executeSwap(
        CrossChainSwapQuote $quote,
        string $walletAddress,
        string $userUuid = '',
    ): array {
        CrossChainSwapInitiated::dispatch(
            $quote->sourceChain,
            $quote->destChain,
            $quote->inputToken,
            $quote->outputToken,
            $quote->inputAmount,
            $walletAddress,
            $quote->quoteId,
        );

        Log::info('Cross-chain swap: initiating', [
            'quote_id' => $quote->quoteId,
            'route'    => "{$quote->sourceChain->value} → {$quote->destChain->value}",
            'pair'     => "{$quote->inputToken}/{$quote->outputToken}",
            'amount'   => $quote->inputAmount,
        ]);

        // Step 1: Bridge
        $bridgeResult = $this->saga->executeBridge($quote, $walletAddress, $userUuid);

        // Step 2: Swap on destination (if needed)
        $swapTxHash = null;
        $outputAmount = $quote->bridgeQuote->outputAmount;

        if ($quote->requiresSwap() && $quote->swapQuote !== null) {
            $swapResult = $this->saga->executeSwapAfterBridge($quote, $walletAddress, $bridgeResult);
            $swapTxHash = $swapResult['tx_hash'];
            $outputAmount = $swapResult['output_amount'];
        }

        CrossChainSwapCompleted::dispatch(
            $quote->sourceChain,
            $quote->destChain,
            $quote->inputToken,
            $quote->outputToken,
            $quote->inputAmount,
            $outputAmount,
            $walletAddress,
            $bridgeResult['transaction_id'],
            $swapTxHash,
        );

        Log::info('Cross-chain swap: completed', [
            'quote_id'  => $quote->quoteId,
            'bridge_tx' => $bridgeResult['transaction_id'],
            'swap_tx'   => $swapTxHash,
            'output'    => $outputAmount,
        ]);

        return [
            'bridge_tx'     => $bridgeResult['transaction_id'],
            'swap_tx'       => $swapTxHash,
            'output_amount' => $outputAmount,
            'status'        => 'completed',
        ];
    }
}
