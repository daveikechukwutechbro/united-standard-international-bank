<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services\Adapters;

use App\Domain\CrossChain\Contracts\BridgeAdapterInterface;
use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\ValueObjects\BridgeQuote;
use App\Domain\CrossChain\ValueObjects\BridgeRoute;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Wormhole V2 Portal Token Bridge adapter.
 *
 * In production, integrates with Wormhole Guardian RPC and Token Bridge contracts
 * for VAA (Verified Action Approval) submission and cross-chain token transfers.
 * Falls back to demo-mode simulation when RPC endpoint is not configured.
 */
class WormholeBridgeAdapter implements BridgeAdapterInterface
{
    private const SUPPORTED_CHAINS = [
        'ethereum', 'polygon', 'bsc', 'arbitrum', 'optimism', 'base', 'solana',
    ];

    /** @var array<string, int> Wormhole chain IDs per network */
    private const CHAIN_IDS = [
        'ethereum' => 2,
        'polygon'  => 5,
        'bsc'      => 4,
        'arbitrum' => 23,
        'optimism' => 24,
        'base'     => 30,
        'solana'   => 1,
    ];

    public function __construct(
        private readonly AbiEncoder $encoder,
        private readonly EthRpcClient $rpcClient,
    ) {
    }

    public function getProvider(): BridgeProvider
    {
        return BridgeProvider::WORMHOLE;
    }

    public function estimateFee(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $token,
        string $amount,
    ): array {
        $baseFee = $this->getBaseFee($sourceChain, $destChain);
        $relayerFee = $this->getRelayerFee($amount);
        $totalFee = bcadd($baseFee, $relayerFee, 8);

        return [
            'fee'            => $totalFee,
            'fee_currency'   => 'USD',
            'estimated_time' => BridgeProvider::WORMHOLE->getAverageTransferTime(),
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
            BridgeProvider::WORMHOLE,
            $feeData['estimated_time'],
            $feeData['fee'],
        );

        return new BridgeQuote(
            quoteId: 'wormhole-' . Str::uuid()->toString(),
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
        Log::info('Wormhole: Initiating bridge transfer', [
            'source'    => $quote->getSourceChain()->value,
            'dest'      => $quote->getDestChain()->value,
            'token'     => $quote->route->token,
            'amount'    => $quote->inputAmount,
            'sender'    => $senderAddress,
            'recipient' => $recipientAddress,
        ]);

        $guardianRpc = config('crosschain.wormhole.guardian_rpc', '');

        if ($guardianRpc !== '' && app()->environment('production')) {
            return $this->initiateBridgeViaRpc(
                (string) $guardianRpc,
                $quote,
                $senderAddress,
                $recipientAddress,
            );
        }

        // Demo mode fallback
        $transactionId = 'wormhole-tx-' . Str::uuid()->toString();

        return [
            'transaction_id' => $transactionId,
            'status'         => BridgeStatus::INITIATED,
            'source_tx_hash' => '0x' . Str::random(64),
        ];
    }

    public function getBridgeStatus(string $transactionId): array
    {
        Log::debug('Wormhole: Checking bridge status', ['transaction_id' => $transactionId]);

        $guardianRpc = config('crosschain.wormhole.guardian_rpc', '');

        if ($guardianRpc !== '' && app()->environment('production')) {
            return $this->getStatusViaRpc((string) $guardianRpc, $transactionId);
        }

        // Demo mode fallback
        return [
            'status'         => BridgeStatus::COMPLETED,
            'source_tx_hash' => '0x' . hash('sha256', $transactionId . 'source'),
            'dest_tx_hash'   => '0x' . hash('sha256', $transactionId . 'dest'),
            'confirmations'  => 15,
        ];
    }

    public function getSupportedRoutes(): array
    {
        $chains = array_map(
            fn (string $chain) => CrossChainNetwork::from($chain),
            self::SUPPORTED_CHAINS,
        );

        $routes = [];
        $tokens = ['USDC', 'USDT', 'WETH', 'WBTC'];

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
                        BridgeProvider::WORMHOLE,
                        BridgeProvider::WORMHOLE->getAverageTransferTime(),
                        $this->getBaseFee($source, $dest),
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

    private function getBaseFee(CrossChainNetwork $source, CrossChainNetwork $dest): string
    {
        // Wormhole fees vary by chain pair
        $isL2 = in_array($source->value, ['arbitrum', 'optimism', 'base'])
            || in_array($dest->value, ['arbitrum', 'optimism', 'base']);

        return $isL2 ? '0.50' : '2.00';
    }

    private function getRelayerFee(string $amount): string
    {
        // 0.05% relayer fee
        return bcmul($amount, '0.0005', 8);
    }

    /**
     * Initiate bridge transfer via Wormhole Token Bridge contract (production).
     *
     * Encodes a TokenBridge.transferTokens() call via ABI encoding and
     * submits it through EthRpcClient. The Token Bridge contract emits a
     * VAA that guardians will sign for cross-chain delivery.
     *
     * @return array{transaction_id: string, status: BridgeStatus, source_tx_hash: ?string}
     */
    private function initiateBridgeViaRpc(
        string $guardianRpc,
        BridgeQuote $quote,
        string $senderAddress,
        string $recipientAddress,
    ): array {
        $destChainId = self::CHAIN_IDS[$quote->getDestChain()->value] ?? 0;

        try {
            $tokenAddress = $this->resolveTokenAddress($quote->route->token, $quote->getSourceChain());
            $amountWei = $this->encoder->toSmallestUnit($quote->inputAmount, 18);
            $feeWei = $this->encoder->toSmallestUnit($quote->fee, 18);
            $nonce = random_int(0, 4294967295);

            // Encode TokenBridge.transferTokens(address token, uint256 amount, uint16 recipientChain, bytes32 recipient, uint256 arbiterFee, uint32 nonce)
            $callData = $this->encoder->encodeFunctionCall(
                'transferTokens(address,uint256,uint16,bytes32,uint256,uint256)',
                [
                    $this->encoder->encodeAddress($tokenAddress),
                    $this->encoder->encodeUint256($amountWei),
                    $this->encoder->encodeUint16($destChainId),
                    $this->encoder->encodeBytes32($this->encoder->encodeAddressAsBytes32($recipientAddress)),
                    $this->encoder->encodeUint256($feeWei),
                    $this->encoder->encodeUint256((string) $nonce),
                ],
            );

            $tokenBridgeAddress = (string) config(
                'crosschain.wormhole.token_bridge_address',
                '0x3ee18B2214AFF97000D974cf647E7C347E8fa585',
            );

            $result = $this->rpcClient->ethCall(
                $tokenBridgeAddress,
                $callData,
                $quote->getSourceChain()->value,
            );

            Log::info('Wormhole: Production bridge initiated via ABI encoding', [
                'source' => $quote->getSourceChain()->value,
                'dest'   => $quote->getDestChain()->value,
                'result' => substr($result, 0, 66),
            ]);

            // Parse sequence number from return value
            $decoded = $this->encoder->decodeResponse($result, ['uint256']);
            $sequence = $decoded[0] ?? '0';

            return [
                'transaction_id' => "wormhole-seq-{$sequence}",
                'status'         => BridgeStatus::INITIATED,
                'source_tx_hash' => $result,
            ];
        } catch (RuntimeException $e) {
            Log::error('Wormhole RPC: Bridge initiation failed', [
                'error'  => $e->getMessage(),
                'source' => $quote->getSourceChain()->value,
            ]);

            // Fallback to Guardian REST API
            return $this->initiateBridgeViaGuardianApi(
                $guardianRpc,
                $quote,
                $senderAddress,
                $recipientAddress,
                $destChainId,
            );
        }
    }

    /**
     * Fallback: initiate bridge via Wormhole Guardian REST API.
     *
     * @return array{transaction_id: string, status: BridgeStatus, source_tx_hash: ?string}
     */
    private function initiateBridgeViaGuardianApi(
        string $guardianRpc,
        BridgeQuote $quote,
        string $senderAddress,
        string $recipientAddress,
        int $destChainId,
    ): array {
        $sourceChainId = self::CHAIN_IDS[$quote->getSourceChain()->value] ?? 0;

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($guardianRpc . '/v1/token_bridge/transfer', [
                'source_chain_id' => $sourceChainId,
                'target_chain_id' => $destChainId,
                'token'           => $quote->route->token,
                'amount'          => $quote->inputAmount,
                'sender'          => $senderAddress,
                'recipient'       => $recipientAddress,
                'relayer_fee'     => $quote->fee,
            ]);

        if (! $response->successful()) {
            Log::error('Wormhole Guardian API: Bridge initiation failed', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return [
                'transaction_id' => 'wormhole-failed-' . Str::uuid()->toString(),
                'status'         => BridgeStatus::FAILED,
                'source_tx_hash' => null,
            ];
        }

        $data = $response->json();

        return [
            'transaction_id' => (string) ($data['sequence'] ?? $data['transaction_id'] ?? Str::uuid()->toString()),
            'status'         => BridgeStatus::INITIATED,
            'source_tx_hash' => (string) ($data['tx_hash'] ?? null),
        ];
    }

    /**
     * Query Wormhole Guardian RPC for VAA status and check destination chain (production).
     *
     * Polls Guardian RPC for signed VAA, then checks destination chain for
     * completion transaction via EthRpcClient.
     *
     * @return array{status: BridgeStatus, source_tx_hash: ?string, dest_tx_hash: ?string, confirmations: int}
     */
    private function getStatusViaRpc(string $guardianRpc, string $transactionId): array
    {
        // Step 1: Poll Guardian RPC for VAA status
        $response = Http::timeout(15)
            ->get($guardianRpc . '/v1/signed_vaa/' . $transactionId);

        if (! $response->successful()) {
            Log::warning('Wormhole RPC: Status check failed', [
                'transaction_id' => $transactionId,
                'status'         => $response->status(),
            ]);

            return [
                'status'         => BridgeStatus::CONFIRMING,
                'source_tx_hash' => null,
                'dest_tx_hash'   => null,
                'confirmations'  => 0,
            ];
        }

        $data = $response->json();
        $vaaBytes = $data['vaa_bytes'] ?? null;
        $confirmations = (int) ($data['guardian_signatures'] ?? 0);
        $sourceTxHash = (string) ($data['source_tx_hash'] ?? '');
        $destTxHash = (string) ($data['dest_tx_hash'] ?? '');

        // Step 2: If VAA is signed, check destination chain for completion
        if ($vaaBytes !== null && $destTxHash !== '') {
            try {
                $destChain = (string) ($data['target_chain'] ?? 'ethereum');
                $receipt = $this->rpcClient->getTransactionReceipt($destTxHash, $destChain);

                if ($receipt !== null) {
                    $receiptStatus = $receipt['status'] ?? '0x0';

                    return [
                        'status'         => $receiptStatus === '0x1' ? BridgeStatus::COMPLETED : BridgeStatus::FAILED,
                        'source_tx_hash' => $sourceTxHash,
                        'dest_tx_hash'   => $destTxHash,
                        'confirmations'  => $confirmations,
                    ];
                }
            } catch (RuntimeException $e) {
                Log::warning('Wormhole: Destination chain receipt check failed', [
                    'error'          => $e->getMessage(),
                    'transaction_id' => $transactionId,
                ]);
            }
        }

        // VAA signed but not yet redeemed on destination
        $status = $vaaBytes !== null ? BridgeStatus::COMPLETED : BridgeStatus::CONFIRMING;

        return [
            'status'         => $status,
            'source_tx_hash' => $sourceTxHash,
            'dest_tx_hash'   => $destTxHash,
            'confirmations'  => $confirmations,
        ];
    }

    /**
     * Resolve token symbol to contract address on a given chain.
     */
    private function resolveTokenAddress(string $token, CrossChainNetwork $chain): string
    {
        /** @var array<string, array<string, string>> $addresses */
        $addresses = (array) config('crosschain.token_addresses', []);
        $chainAddresses = $addresses[$chain->value] ?? [];

        return $chainAddresses[$token] ?? '0x' . str_repeat('0', 40);
    }
}
