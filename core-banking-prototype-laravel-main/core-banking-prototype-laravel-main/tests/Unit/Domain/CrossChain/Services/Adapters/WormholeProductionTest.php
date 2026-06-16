<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\Adapters\WormholeBridgeAdapter;
use App\Infrastructure\Web3\AbiEncoder;
use App\Infrastructure\Web3\EthRpcClient;

uses(Tests\TestCase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
    $this->encoder = new AbiEncoder();
    $this->adapter = new WormholeBridgeAdapter($this->encoder, new EthRpcClient());
});

describe('Wormhole Production Mode', function () {
    it('uses demo mode when guardian RPC is not configured', function () {
        config(['crosschain.wormhole.guardian_rpc' => '']);

        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
        );

        $result = $this->adapter->initiateBridge($quote, '0xSender', '0xRecipient');

        expect($result['transaction_id'])->toStartWith('wormhole-tx-');
        expect($result['status'])->toBe(BridgeStatus::INITIATED);
    });

    it('uses demo mode when not in production environment', function () {
        config(['crosschain.wormhole.guardian_rpc' => 'https://guardian.example.com']);

        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'WETH',
            '5.00',
        );

        // App is in 'testing' environment, not 'production'
        $result = $this->adapter->initiateBridge($quote, '0xSender', '0xRecipient');

        expect($result['transaction_id'])->toStartWith('wormhole-tx-');
        expect($result['status'])->toBe(BridgeStatus::INITIATED);
    });

    it('encodes transferTokens call correctly via AbiEncoder', function () {
        $tokenAddress = '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48';
        $amountWei = $this->encoder->toSmallestUnit('1000.00', 18);
        $recipientBytes32 = str_pad(ltrim('0xRecipientAddress', '0x'), 64, '0', STR_PAD_LEFT);

        $callData = $this->encoder->encodeFunctionCall(
            'transferTokens(address,uint256,uint16,bytes32,uint256,uint256)',
            [
                $this->encoder->encodeAddress($tokenAddress),
                $this->encoder->encodeUint256($amountWei),
                $this->encoder->encodeUint16(5), // Polygon chain ID
                $this->encoder->encodeBytes32($recipientBytes32),
                $this->encoder->encodeUint256('0'), // No arbiter fee
                $this->encoder->encodeUint256('42'), // Nonce
            ],
        );

        expect($callData)->toStartWith('0x');
        // 0x + 8 (selector) + 6 * 64 (6 params)
        expect(strlen($callData))->toBe(2 + 8 + 384);
    });

    it('returns demo status when guardian RPC is not configured', function () {
        config(['crosschain.wormhole.guardian_rpc' => '']);

        $result = $this->adapter->getBridgeStatus('wormhole-tx-123');

        expect($result['status'])->toBe(BridgeStatus::COMPLETED);
        expect($result['confirmations'])->toBe(15);
    });

    it('generates valid quotes with fee calculation', function () {
        $quote = $this->adapter->getQuote(
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::BASE,
            'USDC',
            '10000.00',
        );

        expect($quote->getProvider())->toBe(BridgeProvider::WORMHOLE);
        expect(bccomp($quote->fee, '0', 8))->toBe(1);
        // Output should be less than input (fees deducted)
        expect(bccomp($quote->outputAmount, $quote->inputAmount, 8))->toBe(-1);
    });

    it('supports all expected chain routes', function () {
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::SOLANA, 'USDC'))->toBeTrue();
        expect($this->adapter->supportsRoute(CrossChainNetwork::POLYGON, CrossChainNetwork::BSC, 'WETH'))->toBeTrue();
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::BITCOIN, 'USDC'))->toBeFalse();
        // Same chain should not be supported
        expect($this->adapter->supportsRoute(CrossChainNetwork::ETHEREUM, CrossChainNetwork::ETHEREUM, 'USDC'))->toBeFalse();
    });
});
