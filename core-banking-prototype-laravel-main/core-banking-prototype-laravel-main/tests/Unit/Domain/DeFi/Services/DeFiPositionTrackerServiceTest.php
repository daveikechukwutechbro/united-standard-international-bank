<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionStatus;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\DeFiPositionTrackerService;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
    $this->tracker = new DeFiPositionTrackerService();
});

describe('DeFiPositionTrackerService', function () {
    it('opens and retrieves a position', function () {
        $position = $this->tracker->openPosition(
            DeFiProtocol::AAVE_V3,
            DeFiPositionType::SUPPLY,
            CrossChainNetwork::ETHEREUM,
            'USDC',
            '1000.00',
            '1000.00',
            '3.50',
            '0xWallet1',
        );

        expect($position->positionId)->toStartWith('pos-');
        expect($position->protocol)->toBe(DeFiProtocol::AAVE_V3);
        expect($position->status)->toBe(DeFiPositionStatus::ACTIVE);
    });

    it('closes a position', function () {
        $position = $this->tracker->openPosition(
            DeFiProtocol::LIDO,
            DeFiPositionType::STAKE,
            CrossChainNetwork::ETHEREUM,
            'ETH',
            '10.00',
            '25000.00',
            '3.80',
            '0xWallet2',
        );

        $this->tracker->closePosition('0xWallet2', $position->positionId);

        $active = $this->tracker->getActivePositions('0xWallet2');
        expect($active)->toBeEmpty();
    });

    it('filters positions by chain', function () {
        $this->tracker->openPosition(DeFiProtocol::AAVE_V3, DeFiPositionType::SUPPLY, CrossChainNetwork::ETHEREUM, 'USDC', '1000.00', '1000.00', '3.50', '0xWallet3');
        $this->tracker->openPosition(DeFiProtocol::AAVE_V3, DeFiPositionType::SUPPLY, CrossChainNetwork::POLYGON, 'USDC', '500.00', '500.00', '4.00', '0xWallet3');

        $ethPositions = $this->tracker->getActivePositions('0xWallet3', chain: CrossChainNetwork::ETHEREUM);
        expect($ethPositions)->toHaveCount(1);
    });

    it('filters positions by protocol', function () {
        $this->tracker->openPosition(DeFiProtocol::AAVE_V3, DeFiPositionType::SUPPLY, CrossChainNetwork::ETHEREUM, 'USDC', '1000.00', '1000.00', '3.50', '0xWallet4');
        $this->tracker->openPosition(DeFiProtocol::LIDO, DeFiPositionType::STAKE, CrossChainNetwork::ETHEREUM, 'ETH', '5.00', '12500.00', '3.80', '0xWallet4');

        $aavePositions = $this->tracker->getActivePositions('0xWallet4', protocol: DeFiProtocol::AAVE_V3);
        expect($aavePositions)->toHaveCount(1);
    });

    it('detects at-risk positions', function () {
        $this->tracker->openPosition(DeFiProtocol::AAVE_V3, DeFiPositionType::BORROW, CrossChainNetwork::ETHEREUM, 'USDC', '5000.00', '5000.00', '5.20', '0xWallet5', '1.2');
        $this->tracker->openPosition(DeFiProtocol::AAVE_V3, DeFiPositionType::SUPPLY, CrossChainNetwork::ETHEREUM, 'ETH', '10.00', '25000.00', '2.00', '0xWallet5', '2.5');

        $atRisk = $this->tracker->getAtRiskPositions('0xWallet5');
        expect($atRisk)->toHaveCount(1);
    });

    it('throws exception when closing non-existent position', function () {
        $this->tracker->closePosition('0xWalletMissing', 'pos-nonexistent');
    })->throws(InvalidArgumentException::class, 'Position pos-nonexistent not found');

    it('calculates total portfolio value', function () {
        $this->tracker->openPosition(DeFiProtocol::AAVE_V3, DeFiPositionType::SUPPLY, CrossChainNetwork::ETHEREUM, 'USDC', '1000.00', '1000.00', '3.50', '0xWallet6');
        $this->tracker->openPosition(DeFiProtocol::LIDO, DeFiPositionType::STAKE, CrossChainNetwork::ETHEREUM, 'ETH', '2.00', '5000.00', '3.80', '0xWallet6');

        $total = $this->tracker->getTotalValue('0xWallet6');
        expect($total)->toBe('6000.00');
    });
});
