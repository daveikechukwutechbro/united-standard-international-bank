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
 * Axelar GMP (General Message Passing) bridge adapter.
 *
 * In production, integrates with Axelar Gateway for cross-chain token transfers and messaging.
 * Currently uses demo-mode simulation with production-ready interface.
 */
class AxelarBridgeAdapter implements BridgeAdapterInterface
{
    private const SUPPORTED_CHAINS = [
        'ethereum', 'polygon', 'bsc', 'arbitrum', 'optimism', 'base',
    ];

    public function getProvider(): BridgeProvider
    {
        return BridgeProvider::AXELAR;
    }

    public function estimateFee(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $gasFee = $this->estimateGasFee($sourceChain, $destChain);
        // Axelar charges a flat relayer fee
        $relayerFee = '0.25';
        $totalFee = bcadd($gasFee, $relayerFee, 8);

        return [
            'fee'            => $totalFee,
            'fee_currency'   => 'USD',
            'estimated_time' => BridgeProvider::AXELAR->getAverageTransferTime(),
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
            BridgeProvider::AXELAR,
            $feeData['estimated_time'],
            $feeData['fee'],
        );

        return new BridgeQuote(
            quoteId: 'axelar-' . Str::uuid()->toString(),
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
        Log::info('Axelar: Initiating GMP transfer', [
            'source' => $quote->getSourceChain()->value,
            'dest'   => $quote->getDestChain()->value,
            'token'  => $quote->route->token,
            'amount' => $quote->inputAmount,
        ]);

        // In production: call Axelar Gateway contract to initiate GMP transfer
        $transactionId = 'axelar-tx-' . Str::uuid()->toString();

        return [
            'transaction_id' => $transactionId,
            'status'         => BridgeStatus::INITIATED,
            'source_tx_hash' => '0x' . Str::random(64),
        ];
    }

    public function getBridgeStatus(string $transactionId): array
    {
        Log::debug('Axelar: Checking GMP status', ['transaction_id' => $transactionId]);

        return [
            'status'         => BridgeStatus::COMPLETED,
            'source_tx_hash' => '0x' . md5($transactionId . 'source'),
            'dest_tx_hash'   => '0x' . md5($transactionId . 'dest'),
            'confirmations'  => 30,
        ];
    }

    public function getSupportedRoutes(): array
    {
        $chains = array_map(
            fn (string $chain) => CrossChainNetwork::from($chain),
            self::SUPPORTED_CHAINS,
        );

        $routes = [];
        $tokens = ['USDC', 'USDT', 'WETH', 'WBTC', 'DAI'];

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
                        BridgeProvider::AXELAR,
                        BridgeProvider::AXELAR->getAverageTransferTime(),
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
        // Axelar GMP has moderate fees
        if ($source === CrossChainNetwork::ETHEREUM || $dest === CrossChainNetwork::ETHEREUM) {
            return '1.50';
        }

        return '0.30';
    }
}
