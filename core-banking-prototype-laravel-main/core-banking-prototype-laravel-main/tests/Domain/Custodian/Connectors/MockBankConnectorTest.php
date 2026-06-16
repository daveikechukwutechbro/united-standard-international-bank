<?php

declare(strict_types=1);

use App\Domain\Account\DataObjects\Money;
use App\Domain\Custodian\Connectors\MockBankConnector;
use App\Domain\Custodian\ValueObjects\TransferRequest;

it('can create mock bank connector', function () {
    $connector = new MockBankConnector([
        'name'     => 'Test Mock Bank',
        'base_url' => 'https://mock.test',
        'timeout'  => 30,
        'debug'    => true,
    ]);

    expect($connector->getName())->toBe('Test Mock Bank');
    expect($connector->isAvailable())->toBeTrue();
    expect($connector->getSupportedAssets())->toBe(['USD', 'EUR', 'GBP', 'BTC', 'ETH']);
});

it('can get account balance', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    $balance = $connector->getBalance('mock-account-1', 'USD');

    expect($balance)->toBeInstanceOf(Money::class);
    expect($balance->getAmount())->toBe(1000000); // $10,000.00
});

it('can get account info', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    $accountInfo = $connector->getAccountInfo('mock-account-1');

    expect($accountInfo->accountId)->toBe('mock-account-1');
    expect($accountInfo->name)->toBe('Mock Business Account');
    expect($accountInfo->status)->toBe('active');
    expect($accountInfo->type)->toBe('business');
    expect($accountInfo->isActive())->toBeTrue();
    expect($accountInfo->isFrozen())->toBeFalse();
    expect($accountInfo->getBalance('USD'))->toBe(1000000);
});

it('can initiate successful transfer', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    $request = TransferRequest::create(
        'mock-account-1',
        'mock-account-2',
        'USD',
        50000, // $500.00
        'TEST-REF-123',
        'Test transfer'
    );

    $receipt = $connector->initiateTransfer($request);

    expect($receipt->isCompleted())->toBeTrue();
    expect($receipt->fromAccount)->toBe('mock-account-1');
    expect($receipt->toAccount)->toBe('mock-account-2');
    expect($receipt->assetCode)->toBe('USD');
    expect($receipt->amount)->toBe(50000);
    expect($receipt->status)->toBe('completed');

    // Verify balances were updated
    $balance1 = $connector->getBalance('mock-account-1', 'USD');
    $balance2 = $connector->getBalance('mock-account-2', 'USD');

    expect($balance1->getAmount())->toBe(950000); // $9,500.00
    expect($balance2->getAmount())->toBe(100000); // $1,000.00
});

it('fails transfer with insufficient funds', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    $request = TransferRequest::create(
        'mock-account-2',
        'mock-account-1',
        'USD',
        10000000, // $100,000.00 (more than available)
        'TEST-REF-456'
    );

    $receipt = $connector->initiateTransfer($request);

    expect($receipt->isFailed())->toBeTrue();
    expect($receipt->status)->toBe('failed');
    expect($receipt->metadata['error'])->toBe('Insufficient balance');
});

it('can get transaction status', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    // First create a transaction
    $request = TransferRequest::create(
        'mock-account-1',
        'mock-account-2',
        'USD',
        10000,
        'TEST-REF-789'
    );

    $receipt = $connector->initiateTransfer($request);
    $transactionId = $receipt->id;

    // Get transaction status
    $status = $connector->getTransactionStatus($transactionId);

    expect($status->id)->toBe($transactionId);
    expect($status->isCompleted())->toBeTrue();
});

it('can validate account', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    expect($connector->validateAccount('mock-account-1'))->toBeTrue();
    expect($connector->validateAccount('mock-account-2'))->toBeTrue();
    expect($connector->validateAccount('non-existent'))->toBeFalse();
});

it('can get transaction history', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    // Create some transactions
    $connector->initiateTransfer(TransferRequest::create(
        'mock-account-1',
        'mock-account-2',
        'USD',
        10000
    ));

    $connector->initiateTransfer(TransferRequest::create(
        'mock-account-2',
        'mock-account-1',
        'USD',
        5000
    ));

    // Get history for account 1
    $history = $connector->getTransactionHistory('mock-account-1');

    expect($history)->toHaveCount(2);
    expect($history[0]['from_account'])->toBe('mock-account-1');
    expect($history[1]['to_account'])->toBe('mock-account-1');
});

it('can cancel pending transaction', function () {
    $connector = new MockBankConnector(['name' => 'Mock Bank']);

    // Mock doesn't have pending transactions, so this will return false
    $result = $connector->cancelTransaction('non-existent-tx');

    expect($result)->toBeFalse();
});
