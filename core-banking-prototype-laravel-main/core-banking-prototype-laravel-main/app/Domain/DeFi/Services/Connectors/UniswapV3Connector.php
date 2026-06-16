<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services\Connectors;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\SwapProtocolInterface;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Traits\UsesDeFiConfig;
use App\Domain\DeFi\ValueObjects\SwapQuote;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Uniswap V3 connector: exact input/output swaps, multi-hop routing.
 *
 * In production, integrates with Uniswap V3 Quoter2 contract via JSON-RPC
 * for on-chain quotes, and SwapRouter02 for swap execution.
 * Falls back to estimated quotes when RPC is not configured.
 */
class UniswapV3Connector implements SwapProtocolInterface
{
    use UsesDeFiConfig;

    private const SUPPORTED_CHAINS = ['ethereum', 'polygon', 'arbitrum', 'optimism', 'base'];

    private const FEE_TIERS = [100, 500, 3000, 10000];

    public function __construct(
        private readonly AbiEncoder $encoder,
        private readonly EthRpcClient $rpcClient,
    ) {
    }

    public function getProtocol(): DeFiProtocol
    {
        return DeFiProtocol::UNISWAP_V3;
    }

    public function getQuote(
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippageTolerance = 0.5,
    ): SwapQuote {
        $rpcUrl = $this->getRpcUrl($chain);

        // Production: query Quoter2 contract via JSON-RPC for exact on-chain quote
        if ($rpcUrl !== null && app()->environment('production')) {
            return $this->getOnChainQuote($rpcUrl, $chain, $fromToken, $toToken, $amount, $slippageTolerance);
        }

        // Demo mode: estimated quote
        $bestFeeTier = $this->findBestFeeTier($fromToken, $toToken, $amount);
        $feeRate = $bestFeeTier / 1000000; // Convert basis points
        $fee = bcmul($amount, (string) $feeRate, 8);
        $priceImpact = $this->estimatePriceImpact($amount);
        $impactFee = bcmul($amount, bcdiv($priceImpact, '100', 8), 8);
        $outputAmount = bcsub(bcsub($amount, $fee, 8), $impactFee, 8);
        $gasEstimate = $this->estimateGas($chain);

        return new SwapQuote(
            quoteId: 'uni-v3-' . Str::uuid()->toString(),
            chain: $chain,
            inputToken: $fromToken,
            outputToken: $toToken,
            inputAmount: $amount,
            outputAmount: $outputAmount,
            priceImpact: $priceImpact,
            protocol: DeFiProtocol::UNISWAP_V3,
            gasEstimate: $gasEstimate,
            feeTier: $bestFeeTier,
            expiresAt: CarbonImmutable::now()->addSeconds((int) config('defi.swap.quote_ttl_seconds', 60)),
        );
    }

    public function executeSwap(SwapQuote $quote, string $walletAddress): array
    {
        Log::info('Uniswap V3: Executing swap', [
            'chain'  => $quote->chain->value,
            'pair'   => "{$quote->inputToken}/{$quote->outputToken}",
            'amount' => $quote->inputAmount,
            'wallet' => $walletAddress,
        ]);

        $rpcUrl = $this->getRpcUrl($quote->chain);

        if ($rpcUrl !== null && app()->environment('production')) {
            return $this->executeSwapViaRpc($rpcUrl, $quote, $walletAddress);
        }

        // Demo mode fallback
        return [
            'tx_hash'       => '0x' . Str::random(64),
            'input_amount'  => $quote->inputAmount,
            'output_amount' => $quote->outputAmount,
            'price_impact'  => $quote->priceImpact,
        ];
    }

    public function getSupportedPairs(CrossChainNetwork $chain): array
    {
        if (! in_array($chain->value, self::SUPPORTED_CHAINS)) {
            return [];
        }

        $tokens = ['USDC', 'USDT', 'WETH', 'WBTC', 'DAI'];
        $pairs = [];

        foreach ($tokens as $from) {
            foreach ($tokens as $to) {
                if ($from === $to) {
                    continue;
                }
                foreach (self::FEE_TIERS as $feeTier) {
                    $pairs[] = ['from' => $from, 'to' => $to, 'fee_tier' => $feeTier];
                }
            }
        }

        return $pairs;
    }

    private function findBestFeeTier(string $fromToken, string $toToken, string $amount): int
    {
        // Stablecoin pairs use lowest fee tier
        $stables = ['USDC', 'USDT', 'DAI'];
        if (in_array($fromToken, $stables) && in_array($toToken, $stables)) {
            return 100;
        }

        // Major pairs use 500 or 3000
        $majors = ['WETH', 'WBTC'];
        if (in_array($fromToken, $majors) || in_array($toToken, $majors)) {
            return bccomp($amount, '10000', 2) > 0 ? 500 : 3000;
        }

        return 3000;
    }

    private function estimatePriceImpact(string $amount): string
    {
        // Larger amounts have higher price impact
        if (bccomp($amount, '100000', 2) > 0) {
            return '0.50';
        }
        if (bccomp($amount, '10000', 2) > 0) {
            return '0.10';
        }

        return '0.03';
    }

    private function estimateGas(CrossChainNetwork $chain): string
    {
        return match ($chain) {
            CrossChainNetwork::ETHEREUM => '15.00',
            CrossChainNetwork::POLYGON  => '0.05',
            CrossChainNetwork::ARBITRUM => '0.30',
            CrossChainNetwork::OPTIMISM => '0.20',
            CrossChainNetwork::BASE     => '0.10',
            default                     => '5.00',
        };
    }

    /**
     * Get on-chain quote from Uniswap V3 Quoter2 contract via EthRpcClient.
     *
     * Encodes Quoter2.quoteExactInputSingle(QuoteExactInputSingleParams) where
     * the struct contains: tokenIn, tokenOut, amountIn, fee, sqrtPriceLimitX96.
     * Decodes response: (uint256 amountOut, uint160 sqrtPriceX96After, uint32 initializedTicksCrossed, uint256 gasEstimate)
     */
    private function getOnChainQuote(
        string $rpcUrl,
        CrossChainNetwork $chain,
        string $fromToken,
        string $toToken,
        string $amount,
        float $slippageTolerance,
    ): SwapQuote {
        $quoterAddress = (string) config('defi.uniswap.quoter_address', '0x61fFE014bA17989E743c5F6cB21bF9697530B21e');
        $bestFeeTier = $this->findBestFeeTier($fromToken, $toToken, $amount);

        try {
            $tokenInAddress = $this->resolveTokenAddress($fromToken, $chain);
            $tokenOutAddress = $this->resolveTokenAddress($toToken, $chain);
            $amountIn = $this->encoder->toSmallestUnit($amount, 18);

            // Encode QuoteExactInputSingleParams struct:
            // {address tokenIn, address tokenOut, uint256 amountIn, uint24 fee, uint160 sqrtPriceLimitX96}
            $structFields = $this->encoder->encodeStruct([
                $this->encoder->encodeAddress($tokenInAddress),
                $this->encoder->encodeAddress($tokenOutAddress),
                $this->encoder->encodeUint256($amountIn),
                $this->encoder->encodeUint256((string) $bestFeeTier),
                $this->encoder->encodeUint256('0'), // sqrtPriceLimitX96 = 0 (no limit)
            ]);

            $callData = $this->encoder->encodeFunctionCall(
                'quoteExactInputSingle((address,address,uint256,uint24,uint160))',
                [$structFields],
            );

            $result = $this->rpcClient->ethCall($quoterAddress, $callData, $chain->value);

            // Decode response: (uint256 amountOut, uint160 sqrtPriceX96After, uint32 initializedTicksCrossed, uint256 gasEstimate)
            $decoded = $this->encoder->decodeResponse($result, ['uint256', 'uint160', 'uint256', 'uint256']);
            $amountOutRaw = $decoded[0] ?? '0';
            $onChainGasEstimate = $decoded[3] ?? '0';

            // Convert from smallest unit back to human-readable
            $amountOut = bcdiv($amountOutRaw, bcpow('10', '18'), 8);
            $priceImpact = $this->estimatePriceImpact($amount);

            Log::info('Uniswap V3: On-chain quote received via ABI encoding', [
                'chain'        => $chain->value,
                'pair'         => "{$fromToken}/{$toToken}",
                'amount_out'   => $amountOut,
                'gas_estimate' => $onChainGasEstimate,
            ]);

            return new SwapQuote(
                quoteId: 'uni-v3-' . Str::uuid()->toString(),
                chain: $chain,
                inputToken: $fromToken,
                outputToken: $toToken,
                inputAmount: $amount,
                outputAmount: $amountOut,
                priceImpact: $priceImpact,
                protocol: DeFiProtocol::UNISWAP_V3,
                gasEstimate: $this->estimateGas($chain),
                feeTier: $bestFeeTier,
                expiresAt: CarbonImmutable::now()->addSeconds((int) config('defi.swap.quote_ttl_seconds', 60)),
            );
        } catch (RuntimeException $e) {
            Log::warning('Uniswap V3: On-chain quote failed, falling back to estimate', [
                'chain' => $chain->value,
                'pair'  => "{$fromToken}/{$toToken}",
                'error' => $e->getMessage(),
            ]);

            // Fallback to estimated quote
            $feeRate = $bestFeeTier / 1000000;
            $feeRateStr = (string) $feeRate;
            $fee = bcmul($amount, $feeRateStr, 8);
            $priceImpact = $this->estimatePriceImpact($amount);
            $impactPercent = bcdiv($priceImpact, '100', 8);
            $impactFee = bcmul($amount, $impactPercent, 8);
            $outputAmount = bcsub(bcsub($amount, $fee, 8), $impactFee, 8);

            return new SwapQuote(
                quoteId: 'uni-v3-est-' . Str::uuid()->toString(),
                chain: $chain,
                inputToken: $fromToken,
                outputToken: $toToken,
                inputAmount: $amount,
                outputAmount: $outputAmount,
                priceImpact: $priceImpact,
                protocol: DeFiProtocol::UNISWAP_V3,
                gasEstimate: $this->estimateGas($chain),
                feeTier: $bestFeeTier,
                expiresAt: CarbonImmutable::now()->addSeconds((int) config('defi.swap.quote_ttl_seconds', 60)),
            );
        }
    }

    /**
     * Execute swap via SwapRouter02 on-chain using ABI encoding (production).
     *
     * Encodes SwapRouter02.exactInputSingle(ExactInputSingleParams) where the struct
     * contains: tokenIn, tokenOut, fee, recipient, amountIn, amountOutMinimum, sqrtPriceLimitX96.
     * amountOutMinimum is calculated as outputAmount * (1 - slippage%).
     *
     * @return array{tx_hash: string, input_amount: string, output_amount: string, price_impact: string}
     */
    private function executeSwapViaRpc(string $rpcUrl, SwapQuote $quote, string $walletAddress): array
    {
        $routerAddress = (string) config('defi.uniswap.router_address', '0x68b3465833fb72A70ecDF485E0e4C7bD8665Fc45');

        try {
            $tokenInAddress = $this->resolveTokenAddress($quote->inputToken, $quote->chain);
            $tokenOutAddress = $this->resolveTokenAddress($quote->outputToken, $quote->chain);
            $amountIn = $this->encoder->toSmallestUnit($quote->inputAmount, 18);

            // Calculate amountOutMinimum with slippage (default 0.5%)
            $slippageMultiplier = bcsub('1', '0.005', 8);
            /** @var numeric-string $outputAmount */
            $outputAmount = $quote->outputAmount;
            $minOutputHuman = bcmul($outputAmount, $slippageMultiplier, 8);
            $amountOutMinimum = $this->encoder->toSmallestUnit($minOutputHuman, 18);

            $feeTier = $quote->feeTier ?? 3000;

            // Encode ExactInputSingleParams struct:
            // {address tokenIn, address tokenOut, uint24 fee, address recipient, uint256 amountIn, uint256 amountOutMinimum, uint160 sqrtPriceLimitX96}
            $structFields = $this->encoder->encodeStruct([
                $this->encoder->encodeAddress($tokenInAddress),
                $this->encoder->encodeAddress($tokenOutAddress),
                $this->encoder->encodeUint256((string) $feeTier),
                $this->encoder->encodeAddress($walletAddress),
                $this->encoder->encodeUint256($amountIn),
                $this->encoder->encodeUint256($amountOutMinimum),
                $this->encoder->encodeUint256('0'), // sqrtPriceLimitX96 = 0 (no limit)
            ]);

            $callData = $this->encoder->encodeFunctionCall(
                'exactInputSingle((address,address,uint24,address,uint256,uint256,uint160))',
                [$structFields],
            );

            $txHash = $this->rpcClient->sendTransaction(
                $walletAddress,
                $routerAddress,
                $callData,
                $quote->chain->value,
            );

            Log::info('Uniswap V3: Swap executed via ABI encoding', [
                'chain'   => $quote->chain->value,
                'pair'    => "{$quote->inputToken}/{$quote->outputToken}",
                'tx_hash' => $txHash,
            ]);

            return [
                'tx_hash'       => $txHash,
                'input_amount'  => $quote->inputAmount,
                'output_amount' => $quote->outputAmount,
                'price_impact'  => $quote->priceImpact,
            ];
        } catch (RuntimeException $e) {
            Log::error('Uniswap V3: Swap execution failed', [
                'chain' => $quote->chain->value,
                'error' => $e->getMessage(),
            ]);

            return [
                'tx_hash'       => '',
                'input_amount'  => $quote->inputAmount,
                'output_amount' => '0',
                'price_impact'  => $quote->priceImpact,
            ];
        }
    }
}
