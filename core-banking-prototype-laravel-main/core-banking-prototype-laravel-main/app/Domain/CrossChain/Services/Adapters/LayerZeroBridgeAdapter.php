<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services\Adapters;

use App\Domain\CrossChain\Contracts\BridgeAdapterInterface;
use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use App\Domain\CrossChain\ValueObjects\BridgeRoute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * LayerZero OFT/ONFT bridge adapter.
 *
 * In production, integrates with LayerZero V2 endpoints for omnichain fungible token transfers.
 * Currently uses demo-mode simulation with production-ready interface.
 */
class LayerZeroBridgeAdapter implements BridgeAdapterInterface
{
    private const SUPPORTED_CHAINS = [
        'ethereum', 'polygon', 'bsc', 'arbitrum', 'optimism', 'base',
    ];

    public function getProvider(): BridgeProvider
    {
        return BridgeProvider::LAYERZERO;
    }

    public function estimateFee(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $gasFee = $this->estimateGasFee($sourceChain, $destChain);
        $protocolFee = '0.10'; // LayerZero protocol fee
        $totalFee = bcadd($gasFee, $protocolFee, 8);

        return [
            'fee'            => $totalFee,
            'fee_currency'   => 'USD',
            'estimated_time' => BridgeProvider::LAYERZERO->getAverageTransferTime(),
        ];
    }

    public function getQuote(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): BridgeQuote {
        $feeData = $this->estimateFee($sourceChain, $destChain, $token, $amount);
        $outputAmount = bcsub($amount, $feeData['fee'], 8);

        $route = new BridgeRoute(
            $sourceChain,
            $destChain,
            $token,
            BridgeProvider::LAYERZERO,
            $feeData['estimated_time'],
            $feeData['fee'],
        );

        return new BridgeQuote(
            quoteId: 'lz-' . Str::uuid()->toString(),
            route: $route,
            inputAmount: $amount,
            outputAmount: $outputAmount,
            fee: $feeData['fee'],
            feeCurrency: $feeData['fee_currency'],
            estimatedTimeSeconds: $feeData['estimated_time'],
            expiresAt: CarbonImmutable::now()->addSeconds((int) config('crosschain.fees.quote_ttl_seconds', 300)),
        );
    }

    public function initiateBridge(
        BridgeQuote $quote,
        string $senderAddress,
        string $recipientAddress,
    ): array {
        Log::info('LayerZero: Initiating OFT transfer', [
            'source' => $quote->getSourceChain()->value,
            'dest'   => $quote->getDestChain()->value,
            'token'  => $quote->route->token,
            'amount' => $quote->inputAmount,
        ]);

        // In production: call LayerZero endpoint to send OFT message
        $transactionId = 'lz-tx-' . Str::uuid()->toString();

        return [
            'transaction_id' => $transactionId,
            'status'         => BridgeStatus::INITIATED,
            'source_tx_hash' => '0x' . Str::random(64),
        ];
    }

    public function getBridgeStatus(string $transactionId): array
    {
        Log::debug('LayerZero: Checking message status', ['transaction_id' => $transactionId]);

        return [
            'status'         => BridgeStatus::COMPLETED,
            'source_tx_hash' => '0x' . md5($transactionId . 'source'),
            'dest_tx_hash'   => '0x' . md5($transactionId . 'dest'),
            'confirmations'  => 20,
        ];
    }

    public function getSupportedRoutes(): array
    {
        $chains = array_map(
            fn (string $chain) => CrossChainNetwork::from($chain),
            self::SUPPORTED_CHAINS,
        );

        $routes = [];
        $tokens = ['USDC', 'USDT', 'WETH'];

        foreach ($chains as $source) {
            foreach ($chains as $dest) {
                if ($source === $dest) {
                    continue;
                }
                foreach ($tokens as $token) {
                    $routes[] = new BridgeRoute(
                        $source,
                        $dest,
                        $token,
                        BridgeProvider::LAYERZERO,
                        BridgeProvider::LAYERZERO->getAverageTransferTime(),
                        $this->estimateGasFee($source, $dest),
                    );
                }
            }
        }

        return $routes;
    }

    public function supportsRoute(CrossChainNetwork $source, CrossChainNetwork $dest, string $token): bool
    {
        return in_array($source->value, self::SUPPORTED_CHAINS)
            && in_array($dest->value, self::SUPPORTED_CHAINS)
            && $source !== $dest;
    }

    private function estimateGasFee(CrossChainNetwork $source, CrossChainNetwork $dest): string
    {
        // LayerZero is generally cheaper for L2-to-L2 transfers
        $isL2ToL2 = in_array($source->value, ['arbitrum', 'optimism', 'base', 'polygon'])
            && in_array($dest->value, ['arbitrum', 'optimism', 'base', 'polygon']);

        return $isL2ToL2 ? '0.15' : '1.00';
    }
}
