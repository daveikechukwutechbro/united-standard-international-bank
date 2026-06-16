<?php

declare(strict_types=1);

use App\Domain\CrossChain\Enums\BridgeProvider;
use App\Domain\CrossChain\Enums\BridgeStatus;
use App\Domain\CrossChain\Services\Adapters\DemoBridgeAdapter;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->adapter = new DemoBridgeAdapter();
});

describe('DemoBridgeAdapter', function () {
    it('returns FAILED for unknown transaction ID', function () {
        $result = $this->adapter->getBridgeStatus('unknown_tx_id');

        expect($result['status'])->toBe(BridgeStatus::FAILED);
        expect($result['dest_tx_hash'])->toBeNull();
        expect($result['confirmations'])->toBe(0);
    });

    it('returns demo as provider', function () {
        expect($this->adapter->getProvider())->toBe(BridgeProvider::DEMO);
    });
});
