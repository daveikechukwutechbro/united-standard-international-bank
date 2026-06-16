<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Services\FlashLoanService;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new FlashLoanService();
});

describe('FlashLoanService', function () {
    it('executes flash loan with fee calculation', function () {
        $result = $this->service->executeFlashLoan(
            CrossChainNetwork::ETHEREUM,
            'USDC',
            '1000000.00',
            '0xCallbackContract',
            ['action' => 'arbitrage'],
        );

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['borrowed_amount'])->toBe('1000000.00');
        expect((float) $result['fee'])->toBeGreaterThan(0);
        expect($result['callback_result']['success'])->toBeTrue();
    });

    it('estimates flash loan fee correctly', function () {
        $fee = $this->service->estimateFee('1000000.00');

        // 0.05% fee = 500 on 1M
        expect($fee)->toBe('500.00000000');
    });

    it('checks availability on supported chains', function () {
        expect($this->service->isAvailable(CrossChainNetwork::ETHEREUM))->toBeTrue();
        expect($this->service->isAvailable(CrossChainNetwork::POLYGON))->toBeTrue();
        expect($this->service->isAvailable(CrossChainNetwork::BITCOIN))->toBeFalse();
        expect($this->service->isAvailable(CrossChainNetwork::SOLANA))->toBeFalse();
    });

    it('includes callback params in result', function () {
        $result = $this->service->executeFlashLoan(
            CrossChainNetwork::ARBITRUM,
            'WETH',
            '100.00',
            '0xContract',
            ['strategy' => 'liquidation'],
        );

        expect($result['callback_result']['params']['strategy'])->toBe('liquidation');
    });
});
