<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\CrossChain\Services\BridgeTransactionTracker;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
    $this->tracker = new BridgeTransactionTracker();
});

describe('BridgeTransactionTracker', function () {
    it('records and retrieves a bridge transaction', function () {
        $this->tracker->recordTransaction(
            'tx-001',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '1000.00',
            BridgeProvider::DEMO,
            '0xSender',
            '0xRecipient',
        );

        $tx = $this->tracker->getTransaction('tx-001');

        expect($tx)->not->toBeNull();
        expect($tx['transaction_id'])->toBe('tx-001');
        expect($tx['source_chain'])->toBe('ethereum');
        expect($tx['dest_chain'])->toBe('polygon');
        expect($tx['status'])->toBe('initiated');
    });

    it('updates transaction status', function () {
        $this->tracker->recordTransaction(
            'tx-002',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'USDT',
            '500.00',
            BridgeProvider::WORMHOLE,
            '0xSender',
            '0xRecipient',
        );

        $this->tracker->updateStatus('tx-002', BridgeStatus::BRIDGING, '0xSourceHash');

        $tx = $this->tracker->getTransaction('tx-002');

        expect($tx['status'])->toBe('bridging');
        expect($tx['source_tx_hash'])->toBe('0xSourceHash');
    });

    it('marks completion timestamp on terminal status', function () {
        $this->tracker->recordTransaction(
            'tx-003',
            CrossChainNetwork::POLYGON,
            CrossChainNetwork::BASE,
            'USDC',
            '250.00',
            BridgeProvider::DEMO,
            '0xSender',
            '0xRecipient',
        );

        $this->tracker->updateStatus('tx-003', BridgeStatus::COMPLETED, '0xSrc', '0xDest');

        $tx = $this->tracker->getTransaction('tx-003');

        expect($tx['status'])->toBe('completed');
        expect($tx['completed_at'])->not->toBeNull();
        expect($tx['dest_tx_hash'])->toBe('0xDest');
    });

    it('gets user transactions', function () {
        $this->tracker->recordTransaction(
            'tx-004',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '100.00',
            BridgeProvider::DEMO,
            '0xUser1',
            '0xRecipient',
        );

        $this->tracker->recordTransaction(
            'tx-005',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'WETH',
            '1.50',
            BridgeProvider::DEMO,
            '0xUser1',
            '0xRecipient',
        );

        $txs = $this->tracker->getUserTransactions('0xUser1');

        expect($txs)->toHaveCount(2);
    });

    it('gets pending transactions only', function () {
        $this->tracker->recordTransaction(
            'tx-006',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '100.00',
            BridgeProvider::DEMO,
            '0xUser2',
            '0xRecipient',
        );

        $this->tracker->recordTransaction(
            'tx-007',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'USDT',
            '200.00',
            BridgeProvider::DEMO,
            '0xUser2',
            '0xRecipient',
        );

        $this->tracker->updateStatus('tx-007', BridgeStatus::COMPLETED, '0xSrc', '0xDest');

        $pending = array_values($this->tracker->getPendingTransactions('0xUser2'));

        expect($pending)->toHaveCount(1);
        expect($pending[0]['transaction_id'])->toBe('tx-006');
    });

    it('returns transaction stats', function () {
        $this->tracker->recordTransaction(
            'tx-008',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::POLYGON,
            'USDC',
            '100.00',
            BridgeProvider::DEMO,
            '0xUser3',
            '0xRecipient',
        );

        $this->tracker->recordTransaction(
            'tx-009',
            CrossChainNetwork::ETHEREUM,
            CrossChainNetwork::ARBITRUM,
            'USDT',
            '200.00',
            BridgeProvider::DEMO,
            '0xUser3',
            '0xRecipient',
        );

        $this->tracker->updateStatus('tx-009', BridgeStatus::COMPLETED);

        $stats = $this->tracker->getTransactionStats('0xUser3');

        expect($stats['initiated'])->toBe(1);
        expect($stats['completed'])->toBe(1);
    });
});
