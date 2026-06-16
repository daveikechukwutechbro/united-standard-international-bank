<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Services\Connectors\LidoConnector;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->connector = new LidoConnector();
});

describe('LidoConnector', function () {
    it('returns lido as protocol', function () {
        expect($this->connector->getProtocol())->toBe(DeFiProtocol::LIDO);
    });

    it('stakes ETH and receives stETH', function () {
        $result = $this->connector->stake(CrossChainNetwork::ETHEREUM, '10.00', '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['staked_amount'])->toBe('10.00');
        expect($result['derivative_token'])->toBe('stETH');
        expect($result['derivative_received'])->toBe('10.00');
    });

    it('requests unstaking with estimated completion time', function () {
        $result = $this->connector->unstake(CrossChainNetwork::ETHEREUM, '5.00', '0xWallet');

        expect($result['tx_hash'])->toStartWith('0x');
        expect($result['unstaked_amount'])->toBe('5.00');
        expect($result['estimated_completion'])->not->toBeEmpty();
    });

    it('returns staking APY', function () {
        $apy = $this->connector->getStakingAPY(CrossChainNetwork::ETHEREUM);

        expect((float) $apy)->toBeGreaterThan(0);
    });

    it('returns staked balance with value', function () {
        $balance = $this->connector->getStakedBalance(CrossChainNetwork::ETHEREUM, '0xWallet');

        expect($balance)->toHaveKey('staked');
        expect($balance)->toHaveKey('derivative_balance');
        expect($balance)->toHaveKey('derivative_token');
        expect($balance['derivative_token'])->toBe('stETH');
        expect($balance)->toHaveKey('value_usd');
    });

    it('returns positive USD value for staked position', function () {
        $balance = $this->connector->getStakedBalance(CrossChainNetwork::ETHEREUM, '0xWallet');

        expect((float) $balance['value_usd'])->toBeGreaterThan(0);
    });

    it('returns realistic staking APY range', function () {
        $apy = (float) $this->connector->getStakingAPY(CrossChainNetwork::ETHEREUM);

        expect($apy)->toBeGreaterThan(0);
        expect($apy)->toBeLessThan(20);
    });
});
