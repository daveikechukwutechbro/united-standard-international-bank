<?php

declare(strict_types=1);

use App\Domain\Account\Models\Account;
use App\Domain\Account\Models\AccountBalance;
use App\Domain\Asset\Models\Asset;

// No need for manual imports - Pest.php handles TestCase and RefreshDatabase for Feature tests

describe('AccountBalance Model', function () {
    it('belongs to an account', function () {
        $account = Account::factory()->create();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
        ]);

        expect($balance->account)->toBeInstanceOf(Account::class);
        expect((string) $balance->account->uuid)->toBe((string) $account->uuid);
    });

    it('belongs to an asset', function () {
        $asset = Asset::where('code', 'USD')->first();
        $account = Account::factory()->create();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code'   => $asset->code,
        ]);

        expect($balance->asset)->toBeInstanceOf(Asset::class);
        expect($balance->asset->code)->toBe($asset->code);
    });

    it('can format balance for display', function () {
        $account = Account::factory()->create();
        $asset = Asset::where('code', 'USD')->first();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code'   => $asset->code,
            'balance'      => 150000,
        ]);

        $formatted = $balance->getFormattedBalance();
        expect($formatted)->toBeString();
    });

    it('can credit balance', function () {
        $account = Account::factory()->create();
        $asset = Asset::where('code', 'USD')->first();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code'   => $asset->code,
            'balance'      => 100000,
        ]);

        $balance->credit(50000);
        expect($balance->fresh()->balance)->toBe(150000);
    });

    it('can debit balance', function () {
        $account = Account::factory()->create();
        $asset = Asset::where('code', 'USD')->first();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code'   => $asset->code,
            'balance'      => 100000,
        ]);

        $balance->debit(30000);
        expect($balance->fresh()->balance)->toBe(70000);
    });

    it('cannot debit below zero', function () {
        $account = Account::factory()->create();
        $asset = Asset::where('code', 'USD')->first();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code'   => $asset->code,
            'balance'      => 100000,
        ]);

        expect(fn () => $balance->debit(150000))
            ->toThrow(Exception::class, 'Insufficient balance');
    });

    it('can check if balance is sufficient', function () {
        $account = Account::factory()->create();
        $asset = Asset::where('code', 'USD')->first();
        $balance = AccountBalance::factory()->create([
            'account_uuid' => $account->uuid,
            'asset_code'   => $asset->code,
            'balance'      => 100000,
        ]);

        expect($balance->hasSufficientBalance(50000))->toBeTrue();
        expect($balance->hasSufficientBalance(150000))->toBeFalse();
    });

    it('can get zero balance for new account balance', function () {
        $balance = new AccountBalance([
            'balance' => 0,
        ]);

        expect($balance->balance)->toBe(0);
    });
});
