<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\Connectors\UniswapV3Connector;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
    $this->encoder = new AbiEncoder();
    $this->connector = new UniswapV3Connector($this->encoder, new EthRpcClient());
});

describe('Uniswap V3 Production Mode', function () {
    it('uses demo mode when RPC URL is not configured', function () {
        config(['defi.rpc_urls.ethereum' => '']);

        $quote = $this->connector->getQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        expect($quote->protocol)->toBe(DeFiProtocol::UNISWAP_V3);
        expect(bccomp($quote->outputAmount, '0', 8))->toBe(1);
    });

    it('uses demo mode when not in production environment', function () {
        config(['defi.rpc_urls.ethereum' => 'https://rpc.example.com']);

        $quote = $this->connector->getQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '1000.00',
        );

        // App is in 'testing' environment, not 'production'
        expect($quote->protocol)->toBe(DeFiProtocol::UNISWAP_V3);
        expect($quote->quoteId)->toStartWith('uni-v3-');
    });

    it('encodes quoteExactInputSingle struct correctly', function () {
        $tokenIn = '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48';
        $tokenOut = '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2';
        $amountIn = $this->encoder->toSmallestUnit('1000.00', 18);

        // Encode QuoteExactInputSingleParams struct
        $structFields = $this->encoder->encodeStruct([
            $this->encoder->encodeAddress($tokenIn),
            $this->encoder->encodeAddress($tokenOut),
            $this->encoder->encodeUint256($amountIn),
            $this->encoder->encodeUint256('3000'), // Fee tier
            $this->encoder->encodeUint256('0'),    // sqrtPriceLimitX96
        ]);

        $callData = $this->encoder->encodeFunctionCall(
            'quoteExactInputSingle((address,address,uint256,uint24,uint160))',
            [$structFields],
        );

        expect($callData)->toStartWith('0x');
        // 0x + 8 (selector) + 5 * 64 (5 struct fields)
        expect(strlen($callData))->toBe(2 + 8 + 320);
    });

    it('encodes exactInputSingle struct with slippage protection', function () {
        $tokenIn = '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48';
        $tokenOut = '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2';
        $wallet = '0x742d35Cc6634C0532925a3b844Bc9e7595f2bD18';

        $amountIn = $this->encoder->toSmallestUnit('1000.00', 18);
        $outputAmount = '995.00';

        // Calculate amountOutMinimum with 0.5% slippage
        $slippageMultiplier = bcsub('1', '0.005', 8);
        $minOutput = bcmul($outputAmount, $slippageMultiplier, 8);
        $amountOutMinimum = $this->encoder->toSmallestUnit($minOutput, 18);

        // Encode ExactInputSingleParams struct
        $structFields = $this->encoder->encodeStruct([
            $this->encoder->encodeAddress($tokenIn),
            $this->encoder->encodeAddress($tokenOut),
            $this->encoder->encodeUint256('3000'), // Fee tier
            $this->encoder->encodeAddress($wallet),
            $this->encoder->encodeUint256($amountIn),
            $this->encoder->encodeUint256($amountOutMinimum),
            $this->encoder->encodeUint256('0'), // sqrtPriceLimitX96
        ]);

        $callData = $this->encoder->encodeFunctionCall(
            'exactInputSingle((address,address,uint24,address,uint256,uint256,uint160))',
            [$structFields],
        );

        expect($callData)->toStartWith('0x');
        // 0x + 8 (selector) + 7 * 64 (7 struct fields)
        expect(strlen($callData))->toBe(2 + 8 + 448);

        // Verify slippage calculation
        expect(bccomp($amountOutMinimum, '0', 0))->toBe(1);
        expect(bccomp($amountOutMinimum, $this->encoder->toSmallestUnit($outputAmount, 18), 0))->toBe(-1);
    });

    it('decodes Quoter2 response correctly', function () {
        $amountOut = '999500000000000000000'; // ~999.5 tokens
        $sqrtPriceX96After = '79228162514264337593543950336';
        $initializedTicksCrossed = '2';
        $gasEstimate = '150000';

        $encoded = '0x'
            . $this->encoder->encodeUint256($amountOut)
            . $this->encoder->encodeUint256($sqrtPriceX96After)
            . $this->encoder->encodeUint256($initializedTicksCrossed)
            . $this->encoder->encodeUint256($gasEstimate);

        $decoded = $this->encoder->decodeResponse($encoded, ['uint256', 'uint160', 'uint256', 'uint256']);

        expect($decoded[0])->toBe($amountOut);
        expect($decoded[3])->toBe($gasEstimate);
    });

    it('executes swap in demo mode and returns tx hash', function () {
        config(['defi.rpc_urls.polygon' => '']);

        $quote = $this->connector->getQuote(
            CrossChainNetwork::POLYGON,
            'WETH',
            'USDC',
            '5.00',
        );

        $result = $this->connector->executeSwap($quote, '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['input_amount'])->toBe('5.00');
        expect(bccomp($result['output_amount'], '0', 8))->toBe(1);
    });

    it('selects correct fee tier for stablecoin pairs', function () {
        $quote = $this->connector->getQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'DAI',
            '10000.00',
        );

        expect($quote->feeTier)->toBe(100);
    });

    it('selects correct fee tier for major pairs', function () {
        // Large amount should use 500 tier
        $largeQuote = $this->connector->getQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '50000.00',
        );

        expect($largeQuote->feeTier)->toBe(500);

        // Small amount should use 3000 tier
        $smallQuote = $this->connector->getQuote(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            'WETH',
            '100.00',
        );

        expect($smallQuote->feeTier)->toBe(3000);
    });
});
