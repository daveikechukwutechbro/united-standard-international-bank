<?php

declare(strict_types=1);

use App\Domain\Ledger\Enums\AccountType;

describe('AccountType enum', function (): void {
    it('has the correct string values', function (): void {
        expect(AccountType::ASSET->value)->toBe('asset');
        expect(AccountType::LIABILITY->value)->toBe('liability');
        expect(AccountType::EQUITY->value)->toBe('equity');
        expect(AccountType::REVENUE->value)->toBe('revenue');
        expect(AccountType::EXPENSE->value)->toBe('expense');
    });

    it('can be created from valid string values', function (): void {
        expect(AccountType::from('asset'))->toBe(AccountType::ASSET);
        expect(AccountType::from('liability'))->toBe(AccountType::LIABILITY);
        expect(AccountType::from('equity'))->toBe(AccountType::EQUITY);
        expect(AccountType::from('revenue'))->toBe(AccountType::REVENUE);
        expect(AccountType::from('expense'))->toBe(AccountType::EXPENSE);
    });

    it('has five cases', function (): void {
        expect(AccountType::cases())->toHaveCount(5);
    });

    it('returns debit normal balance for asset and expense accounts', function (): void {
        expect(AccountType::ASSET->normalBalance())->toBe('debit');
        expect(AccountType::EXPENSE->normalBalance())->toBe('debit');
    });

    it('returns credit normal balance for liability, equity, and revenue accounts', function (): void {
        expect(AccountType::LIABILITY->normalBalance())->toBe('credit');
        expect(AccountType::EQUITY->normalBalance())->toBe('credit');
        expect(AccountType::REVENUE->normalBalance())->toBe('credit');
    });
});
