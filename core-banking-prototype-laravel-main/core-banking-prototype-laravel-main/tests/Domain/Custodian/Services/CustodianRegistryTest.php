<?php

declare(strict_types=1);

use App\Domain\Custodian\Connectors\MockBankConnector;
use App\Domain\Custodian\Exceptions\CustodianNotFoundException;
use App\Domain\Custodian\Services\CustodianRegistry;

beforeEach(function () {
    $this->registry = new CustodianRegistry();
    $this->mockConnector = new MockBankConnector(['name' => 'Mock Bank']);
});

it('can register custodian', function () {
    $this->registry->register('mock', $this->mockConnector);

    expect($this->registry->has('mock'))->toBeTrue();
    expect($this->registry->names())->toContain('mock');
});

it('can get registered custodian', function () {
    $this->registry->register('mock', $this->mockConnector);

    $custodian = $this->registry->get('mock');

    expect($custodian)->toBe($this->mockConnector);
    expect($custodian->getName())->toBe('Mock Bank');
});

it('throws exception for non-existent custodian', function () {
    expect(fn () => $this->registry->get('non-existent'))
        ->toThrow(CustodianNotFoundException::class);
});

it('sets first registered custodian as default', function () {
    $this->registry->register('mock', $this->mockConnector);

    $default = $this->registry->getDefault();

    expect($default)->toBe($this->mockConnector);
});

it('can change default custodian', function () {
    $this->registry->register('mock1', $this->mockConnector);

    $secondConnector = new MockBankConnector(['name' => 'Second Bank']);
    $this->registry->register('mock2', $secondConnector);

    $this->registry->setDefault('mock2');

    expect($this->registry->getDefault())->toBe($secondConnector);
});

it('can get all custodians', function () {
    $this->registry->register('mock1', $this->mockConnector);

    $secondConnector = new MockBankConnector(['name' => 'Second Bank']);
    $this->registry->register('mock2', $secondConnector);

    $all = $this->registry->all();

    expect($all)->toHaveCount(2);
    expect($all['mock1'])->toBe($this->mockConnector);
    expect($all['mock2'])->toBe($secondConnector);
});

it('can get available custodians', function () {
    $this->registry->register('mock', $this->mockConnector);

    $available = $this->registry->available();

    expect($available)->toHaveCount(1);
    expect($available['mock'])->toBe($this->mockConnector);
});

it('can remove custodian', function () {
    $this->registry->register('mock', $this->mockConnector);

    expect($this->registry->has('mock'))->toBeTrue();

    $this->registry->remove('mock');

    expect($this->registry->has('mock'))->toBeFalse();
});

it('can find custodians by supported asset', function () {
    $this->registry->register('mock', $this->mockConnector);

    $usdCustodians = $this->registry->findByAsset('USD');
    $btcCustodians = $this->registry->findByAsset('BTC');
    $jpyCustodians = $this->registry->findByAsset('JPY');

    expect($usdCustodians)->toHaveCount(1);
    expect($btcCustodians)->toHaveCount(1);
    expect($jpyCustodians)->toHaveCount(0);
});

it('throws exception when no default custodian configured', function () {
    expect(fn () => $this->registry->getDefault())
        ->toThrow(CustodianNotFoundException::class);
});
