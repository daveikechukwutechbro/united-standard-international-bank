<?php

declare(strict_types=1);

use App\Domain\Account\DataObjects\Account;
use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Events\AccountCreated;
use App\Domain\Account\Events\AccountDeleted;
use App\Domain\Account\Events\AccountFrozen;
use App\Domain\Account\Events\AccountUnfrozen;
use App\Domain\Account\Events\MoneyAdded;
use App\Domain\Account\Events\MoneySubtracted;
use App\Domain\Account\Events\MoneyTransferred;
use App\Domain\Account\Exceptions\InvalidHashException;
use App\Domain\Account\Exceptions\NotEnoughFunds;
use App\Domain\Asset\Events\AssetTransactionCreated;
use App\Domain\Asset\Events\AssetTransferCompleted;
use App\Domain\Asset\Events\AssetTransferFailed;
use App\Domain\Asset\Events\AssetTransferInitiated;
use App\Domain\User\Values\UserRoles;
use App\Values\EventQueues;
use Illuminate\Support\Str;

// Test all data object methods extensively
it('can test data object methods comprehensively', function () {
    // Test Money object extensively
    $money1 = new Money(5000);
    $money2 = new Money(3000);
    $money3 = new Money(-2000);

    expect($money1->getAmount())->toBe(5000);
    expect($money2->getAmount())->toBe(3000);
    expect($money3->getAmount())->toBe(-2000);

    expect($money1->invert()->getAmount())->toBe(-5000);
    expect($money2->invert()->getAmount())->toBe(-3000);
    expect($money3->invert()->getAmount())->toBe(2000);

    // Test different money amounts
    foreach ([0, 1, 100, 999, 1000, 10000, 99999, 100000] as $amount) {
        $moneyObj = new Money($amount);
        expect($moneyObj->getAmount())->toBe($amount);
        expect($moneyObj->invert()->getAmount())->toBe(-$amount);
    }
});

// Test Hash object with different hash values
it('can test hash object with various values', function () {
    $validHashes = [
        str_repeat('0', 128),
        str_repeat('1', 128),
        str_repeat('a', 128),
        str_repeat('f', 128),
        str_repeat('9', 128),
    ];

    foreach ($validHashes as $hashValue) {
        $hash = new Hash($hashValue);
        expect($hash->getHash())->toBe($hashValue);
        expect(strlen($hash->getHash()))->toBe(128);
    }
});

// Test AccountUuid with different UUID formats
it('can test account uuid with different formats', function () {
    $uuids = [
        '00000000-0000-0000-0000-000000000000',
        '11111111-1111-1111-1111-111111111111',
        '12345678-1234-1234-1234-123456789012',
        'abcdefab-abcd-abcd-abcd-abcdefabcdef',
        'fedcba98-7654-3210-fedc-ba9876543210',
    ];

    foreach ($uuids as $uuid) {
        $accountUuid = new AccountUuid($uuid);
        expect($accountUuid->getUuid())->toBe($uuid);
        expect($accountUuid->toArray())->toHaveKey('uuid');
        expect($accountUuid->toArray()['uuid'])->toBe($uuid);

        // Test withUuid method
        $newUuid = '99999999-9999-9999-9999-999999999999';
        $updatedAccountUuid = $accountUuid->withUuid($newUuid);
        expect($updatedAccountUuid->getUuid())->toBe($newUuid);
        expect($accountUuid->getUuid())->toBe($uuid); // Original unchanged
    }
});

// Test Account data object extensively
it('can test account data object comprehensively', function () {
    $names = ['Test Account', 'Another Account', 'Business Account', 'Personal Savings'];
    $userUuids = [
        '12345678-1234-1234-1234-123456789012',
        'abcdefab-abcd-abcd-abcd-abcdefabcdef',
        'fedcba98-7654-3210-fedc-ba9876543210',
    ];
    $accountUuids = [
        '00000000-0000-0000-0000-000000000000',
        '11111111-1111-1111-1111-111111111111',
        'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    ];

    foreach ($names as $name) {
        foreach ($userUuids as $userUuid) {
            foreach ($accountUuids as $accountUuid) {
                $account = new Account($name, $userUuid, $accountUuid);

                expect($account->toArray())->toHaveKey('name');
                expect($account->toArray())->toHaveKey('user_uuid');
                expect($account->toArray())->toHaveKey('uuid');
                expect($account->toArray()['name'])->toBe($name);
                expect($account->toArray()['user_uuid'])->toBe($userUuid);
                expect($account->toArray()['uuid'])->toBe($accountUuid);
            }
        }
    }
});

// Test all domain events with different parameters
it('can test domain events with various parameters', function () {
    // Test AccountCreated event
    $account = new Account('Test', Str::uuid()->toString(), Str::uuid()->toString());
    $accountCreated = new AccountCreated($account);
    expect($accountCreated->account)->toBe($account);
    expect($accountCreated->queue)->toBe('ledger');

    // Test AccountDeleted event
    $accountDeleted = new AccountDeleted();
    expect($accountDeleted->queue)->toBe('ledger');

    // Test AccountFrozen events with different reasons
    $freezeReasons = [
        'Suspicious activity',
        'Compliance investigation',
        'Manual review required',
        'Fraud prevention',
        'Regulatory hold',
    ];

    foreach ($freezeReasons as $reason) {
        $frozenEvent = new AccountFrozen($reason);
        expect($frozenEvent->reason)->toBe($reason);
        expect($frozenEvent->authorizedBy)->toBeNull();
        expect($frozenEvent->queue)->toBe('ledger');

        // Test with authorized by
        $authorizedBy = 'admin@example.com';
        $frozenEventWithAuth = new AccountFrozen($reason, $authorizedBy);
        expect($frozenEventWithAuth->reason)->toBe($reason);
        expect($frozenEventWithAuth->authorizedBy)->toBe($authorizedBy);
        expect($frozenEventWithAuth->queue)->toBe('ledger');
    }

    // Test AccountUnfrozen events
    $unfreezeReasons = [
        'Investigation completed',
        'Manual review passed',
        'Compliance cleared',
        'Error corrected',
    ];

    foreach ($unfreezeReasons as $reason) {
        $unfrozenEvent = new AccountUnfrozen($reason);
        expect($unfrozenEvent->reason)->toBe($reason);
        expect($unfrozenEvent->authorizedBy)->toBeNull();
        expect($unfrozenEvent->queue)->toBe('ledger');

        // Test with authorized by
        $authorizedBy = 'manager@example.com';
        $unfrozenEventWithAuth = new AccountUnfrozen($reason, $authorizedBy);
        expect($unfrozenEventWithAuth->reason)->toBe($reason);
        expect($unfrozenEventWithAuth->authorizedBy)->toBe($authorizedBy);
        expect($unfrozenEventWithAuth->queue)->toBe('ledger');
    }
});

// Test money events extensively
it('can test money events with different amounts', function () {
    $amounts = [100, 500, 1000, 5000, 10000, 50000, 100000];

    foreach ($amounts as $amount) {
        $money = new Money($amount);
        $hash = new Hash(str_repeat('a', 128));

        // Test MoneyAdded
        $moneyAdded = new MoneyAdded($money, $hash);
        expect($moneyAdded->money)->toBe($money);
        expect($moneyAdded->hash)->toBe($hash);
        expect($moneyAdded->queue)->toBe('transactions');

        // Test MoneySubtracted
        $moneySubtracted = new MoneySubtracted($money, $hash);
        expect($moneySubtracted->money)->toBe($money);
        expect($moneySubtracted->hash)->toBe($hash);
        expect($moneySubtracted->queue)->toBe('transactions');

        // Test MoneyTransferred
        $fromAccountUuid = new AccountUuid(Str::uuid()->toString());
        $toAccountUuid = new AccountUuid(Str::uuid()->toString());
        $moneyTransferred = new MoneyTransferred($fromAccountUuid, $toAccountUuid, $money, $hash);
        expect($moneyTransferred->from)->toBe($fromAccountUuid);
        expect($moneyTransferred->to)->toBe($toAccountUuid);
        expect($moneyTransferred->money)->toBe($money);
        expect($moneyTransferred->hash)->toBe($hash);
        expect($moneyTransferred->queue)->toBe('transfers');
    }
});

// Test asset events extensively
it('can test asset events with different parameters', function () {
    $accountUuids = [
        new AccountUuid(Str::uuid()->toString()),
        new AccountUuid(Str::uuid()->toString()),
        new AccountUuid(Str::uuid()->toString()),
    ];

    $assetCodes = ['USD', 'EUR', 'GBP', 'BTC', 'ETH'];
    $amounts = [1000, 5000, 10000, 25000, 50000];
    $types = ['deposit', 'withdrawal', 'transfer'];

    foreach ($accountUuids as $accountUuid) {
        foreach ($assetCodes as $assetCode) {
            foreach ($amounts as $amount) {
                foreach ($types as $type) {
                    $hash = new Hash(str_repeat('b', 128));

                    // Test AssetTransactionCreated
                    $money = new Money($amount);
                    $assetTransactionCreated = new AssetTransactionCreated(
                        $accountUuid,
                        $assetCode,
                        $money,
                        $type === 'deposit' ? 'credit' : 'debit',
                        $hash
                    );

                    expect($assetTransactionCreated->accountUuid)->toBe($accountUuid);
                    expect($assetTransactionCreated->assetCode)->toBe($assetCode);
                    expect($assetTransactionCreated->getAmount())->toBe($amount);
                    expect($assetTransactionCreated->type)->toBe($type === 'deposit' ? 'credit' : 'debit');
                    expect($assetTransactionCreated->hash)->toBe($hash);

                    // Test credit/debit detection
                    if ($type === 'deposit') {
                        expect($assetTransactionCreated->isCredit())->toBeTrue();
                        expect($assetTransactionCreated->isDebit())->toBeFalse();
                    } else {
                        expect($assetTransactionCreated->isCredit())->toBeFalse();
                        expect($assetTransactionCreated->isDebit())->toBeTrue();
                    }
                }
            }
        }
    }
});

// Test asset transfer events
it('can test asset transfer events comprehensively', function () {
    $fromAccountUuids = [
        new AccountUuid(Str::uuid()->toString()),
        new AccountUuid(Str::uuid()->toString()),
    ];

    $toAccountUuids = [
        new AccountUuid(Str::uuid()->toString()),
        new AccountUuid(Str::uuid()->toString()),
    ];

    $assetPairs = [
        ['USD', 'EUR'],
        ['BTC', 'ETH'],
        ['USD', 'BTC'],
        ['EUR', 'GBP'],
    ];

    $exchangeRates = [1.0, 0.85, 1.18, 50000.0];

    foreach ($fromAccountUuids as $fromAccountUuid) {
        foreach ($toAccountUuids as $toAccountUuid) {
            foreach ($assetPairs as $index => $assetPair) {
                $fromAssetCode = $assetPair[0];
                $toAssetCode = $assetPair[1];
                $exchangeRate = $exchangeRates[$index];
                $fromAmount = 10000;
                $toAmount = (int) ($fromAmount * $exchangeRate);
                $hash = new Hash(str_repeat('c', 128));

                // Test AssetTransferInitiated
                $fromMoney = new Money($fromAmount);
                $toMoney = new Money($toAmount);
                $transferInitiated = new AssetTransferInitiated(
                    $fromAccountUuid,
                    $toAccountUuid,
                    $fromAssetCode,
                    $toAssetCode,
                    $fromMoney,
                    $toMoney,
                    $exchangeRate,
                    $hash
                );

                expect($transferInitiated->fromAccountUuid)->toBe($fromAccountUuid);
                expect($transferInitiated->toAccountUuid)->toBe($toAccountUuid);
                expect($transferInitiated->fromAssetCode)->toBe($fromAssetCode);
                expect($transferInitiated->toAssetCode)->toBe($toAssetCode);
                expect($transferInitiated->getFromAmount())->toBe($fromAmount);
                expect($transferInitiated->getToAmount())->toBe($toAmount);
                expect($transferInitiated->exchangeRate)->toBe($exchangeRate);
                expect($transferInitiated->hash)->toBe($hash);
                expect($transferInitiated->isCrossAssetTransfer())->toBe($fromAssetCode !== $toAssetCode);
                expect($transferInitiated->isSameAssetTransfer())->toBe($fromAssetCode === $toAssetCode);

                // Test AssetTransferCompleted
                $transferCompleted = new AssetTransferCompleted(
                    $fromAccountUuid,
                    $toAccountUuid,
                    $fromAssetCode,
                    $toAssetCode,
                    $fromMoney,
                    $toMoney,
                    $hash
                );

                expect($transferCompleted->fromAccountUuid)->toBe($fromAccountUuid);
                expect($transferCompleted->toAccountUuid)->toBe($toAccountUuid);
                expect($transferCompleted->fromAssetCode)->toBe($fromAssetCode);
                expect($transferCompleted->toAssetCode)->toBe($toAssetCode);
                expect($transferCompleted->fromAmount)->toBe($fromMoney);
                expect($transferCompleted->toAmount)->toBe($toMoney);
                expect($transferCompleted->hash)->toBe($hash);

                // Test AssetTransferFailed
                $failureReasons = [
                    'Insufficient funds',
                    'Invalid exchange rate',
                    'Account not found',
                    'Network error',
                ];

                foreach ($failureReasons as $reason) {
                    $transferFailed = new AssetTransferFailed(
                        $fromAccountUuid,
                        $toAccountUuid,
                        $fromAssetCode,
                        $toAssetCode,
                        $fromMoney,
                        $reason,
                        $hash
                    );

                    expect($transferFailed->fromAccountUuid)->toBe($fromAccountUuid);
                    expect($transferFailed->toAccountUuid)->toBe($toAccountUuid);
                    expect($transferFailed->fromAssetCode)->toBe($fromAssetCode);
                    expect($transferFailed->toAssetCode)->toBe($toAssetCode);
                    expect($transferFailed->fromAmount)->toBe($fromMoney);
                    expect($transferFailed->reason)->toBe($reason);
                    expect($transferFailed->hash)->toBe($hash);
                    expect($transferFailed->getReason())->toBe($reason);

                    // Test failure type detection
                    if (str_contains(strtolower($reason), 'insufficient')) {
                        expect($transferFailed->isInsufficientBalance())->toBeTrue();
                    }
                    if (str_contains(strtolower($reason), 'rate') || str_contains(strtolower($reason), 'exchange')) {
                        expect($transferFailed->isExchangeRateFailure())->toBeTrue();
                    }
                }
            }
        }
    }
});

// Test exceptions extensively
it('can test exceptions with different messages', function () {
    $hashMessages = [
        'Invalid hash provided',
        'Hash validation failed',
        'Malformed hash string',
        'Hash length incorrect',
    ];

    $fundMessages = [
        'Insufficient balance for transaction',
        'Account has no funds',
        'Balance too low',
        'Cannot withdraw more than available',
    ];

    foreach ($hashMessages as $message) {
        $exception = new InvalidHashException($message);
        expect($exception->getMessage())->toBe($message);
        expect($exception)->toBeInstanceOf(Exception::class);
        expect($exception)->toBeInstanceOf(Throwable::class);
    }

    foreach ($fundMessages as $message) {
        $exception = new NotEnoughFunds($message);
        expect($exception->getMessage())->toBe($message);
        expect($exception)->toBeInstanceOf(Exception::class);
        expect($exception)->toBeInstanceOf(Throwable::class);
    }

    // Test default constructors
    $defaultHashException = new InvalidHashException();
    $defaultFundsException = new NotEnoughFunds();

    expect($defaultHashException->getMessage())->toBeString();
    expect($defaultFundsException->getMessage())->toBeString();
});

// Test enum values exhaustively
it('can test enum values exhaustively', function () {
    // Test all UserRoles values
    $allUserRoles = UserRoles::cases();
    expect($allUserRoles)->toHaveCount(3);

    $expectedRoles = ['admin', 'business', 'private'];
    $actualRoles = array_map(fn ($role) => $role->value, $allUserRoles);

    foreach ($expectedRoles as $expectedRole) {
        expect($actualRoles)->toContain($expectedRole);
    }

    expect(UserRoles::ADMIN->value)->toBe('admin');
    expect(UserRoles::BUSINESS->value)->toBe('business');
    expect(UserRoles::PRIVATE->value)->toBe('private');

    // Test all EventQueues values
    $allEventQueues = EventQueues::cases();
    expect($allEventQueues)->toHaveCount(5);

    $expectedQueues = ['events', 'ledger', 'transactions', 'transfers', 'liquidity_pools'];
    $actualQueues = array_map(fn ($queue) => $queue->value, $allEventQueues);

    foreach ($expectedQueues as $expectedQueue) {
        expect($actualQueues)->toContain($expectedQueue);
    }

    expect(EventQueues::EVENTS->value)->toBe('events');
    expect(EventQueues::LEDGER->value)->toBe('ledger');
    expect(EventQueues::TRANSACTIONS->value)->toBe('transactions');
    expect(EventQueues::TRANSFERS->value)->toBe('transfers');

    // Test default method
    expect(EventQueues::default())->toBe(EventQueues::EVENTS);
    expect(EventQueues::default()->value)->toBe('events');
});

// Test additional static methods and edge cases
it('can test additional static methods and edge cases', function () {
    // Test Account fromArray static method
    $accountData = [
        'name'      => 'Test Account',
        'user_uuid' => Str::uuid()->toString(),
        'uuid'      => Str::uuid()->toString(),
    ];

    $account = Account::fromArray($accountData);
    expect($account->toArray()['name'])->toBe($accountData['name']);
    expect($account->toArray()['user_uuid'])->toBe($accountData['user_uuid']);
    expect($account->toArray()['uuid'])->toBe($accountData['uuid']);

    // Test AccountUuid fromArray static method
    $uuidData = ['uuid' => Str::uuid()->toString()];
    $accountUuid = AccountUuid::fromArray($uuidData);
    expect($accountUuid->getUuid())->toBe($uuidData['uuid']);
    expect($accountUuid->toArray()['uuid'])->toBe($uuidData['uuid']);

    // Test edge cases for Money
    $zeroMoney = new Money(0);
    expect($zeroMoney->getAmount())->toBe(0);
    expect($zeroMoney->invert()->getAmount())->toBe(0);

    $negativeMoney = new Money(-1000);
    expect($negativeMoney->getAmount())->toBe(-1000);
    expect($negativeMoney->invert()->getAmount())->toBe(1000);
});

// Test inheritance and interfaces
it('can test inheritance and interfaces', function () {
    // Test that all events extend ShouldBeStored
    $account = new Account('Test', Str::uuid()->toString(), Str::uuid()->toString());
    $money = new Money(1000);
    $hash = new Hash(str_repeat('a', 128));
    $accountUuid = new AccountUuid(Str::uuid()->toString());

    $events = [
        new AccountCreated($account),
        new AccountDeleted(),
        new AccountFrozen('Test reason'),
        new AccountUnfrozen('Test reason'),
        new MoneyAdded($money, $hash),
        new MoneySubtracted($money, $hash),
        new MoneyTransferred($accountUuid, $accountUuid, $money, $hash),
    ];

    foreach ($events as $event) {
        expect($event)->toBeInstanceOf(Spatie\EventSourcing\StoredEvents\ShouldBeStored::class);
    }

    // Test that data objects implement DataObjectContract
    expect($account)->toBeInstanceOf(JustSteveKing\DataObjects\Contracts\DataObjectContract::class);
    expect($accountUuid)->toBeInstanceOf(JustSteveKing\DataObjects\Contracts\DataObjectContract::class);
});
