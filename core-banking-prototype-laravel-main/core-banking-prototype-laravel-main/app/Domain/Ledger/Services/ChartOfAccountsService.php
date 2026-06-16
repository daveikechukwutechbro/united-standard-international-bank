<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services;

use App\Domain\Ledger\Enums\AccountType;
use App\Domain\Ledger\Models\LedgerAccount;
use Illuminate\Support\Collection;

final class ChartOfAccountsService
{
    /**
     * Create a new ledger account.
     */
    public function createAccount(
        string $code,
        string $name,
        AccountType $type,
        ?string $parentCode = null,
        string $currency = 'USD',
        ?string $description = null,
    ): LedgerAccount {
        return LedgerAccount::create([
            'code'        => $code,
            'name'        => $name,
            'type'        => $type,
            'parent_code' => $parentCode,
            'currency'    => $currency,
            'is_active'   => true,
            'description' => $description,
        ]);
    }

    /**
     * Get the full chart of accounts as a flat list.
     *
     * @return Collection<int, LedgerAccount>
     */
    public function getAll(): Collection
    {
        return LedgerAccount::active()->orderBy('code')->get();
    }

    /**
     * Get accounts by type.
     *
     * @return Collection<int, LedgerAccount>
     */
    public function getByType(AccountType $type): Collection
    {
        return LedgerAccount::active()->byType($type)->orderBy('code')->get();
    }

    /**
     * Get root accounts (no parent).
     *
     * @return Collection<int, LedgerAccount>
     */
    public function getRootAccounts(): Collection
    {
        return LedgerAccount::active()->whereNull('parent_code')->orderBy('code')->get();
    }

    /**
     * Deactivate an account (soft disable).
     */
    public function deactivateAccount(string $code): LedgerAccount
    {
        $account = LedgerAccount::where('code', $code)->firstOrFail();
        $account->update(['is_active' => false]);

        return $account->refresh();
    }

    /**
     * Seed the default chart of accounts.
     * Keys are account codes; PHP converts numeric-string keys to int.
     *
     * @return array<int|string, LedgerAccount>
     */
    public function seedDefaultAccounts(): array
    {
        $accounts = [
            ['1000', 'Assets', AccountType::ASSET, null],
            ['1100', 'Cash & Bank', AccountType::ASSET, '1000'],
            ['1110', 'Operating Account', AccountType::ASSET, '1100'],
            ['1120', 'Settlement Account', AccountType::ASSET, '1100'],
            ['1200', 'Loans Receivable', AccountType::ASSET, '1000'],
            ['1300', 'Card Receivables', AccountType::ASSET, '1000'],
            ['1400', 'DeFi Positions', AccountType::ASSET, '1000'],
            ['2000', 'Liabilities', AccountType::LIABILITY, null],
            ['2100', 'Customer Deposits', AccountType::LIABILITY, '2000'],
            ['2200', 'Customer Wallets', AccountType::LIABILITY, '2000'],
            ['2300', 'Pending Settlements', AccountType::LIABILITY, '2000'],
            ['3000', 'Equity', AccountType::EQUITY, null],
            ['3100', 'Retained Earnings', AccountType::EQUITY, '3000'],
            ['4000', 'Revenue', AccountType::REVENUE, null],
            ['4100', 'Transaction Fees', AccountType::REVENUE, '4000'],
            ['4200', 'Interest Income', AccountType::REVENUE, '4000'],
            ['4300', 'Exchange Revenue', AccountType::REVENUE, '4000'],
            ['5000', 'Expenses', AccountType::EXPENSE, null],
            ['5100', 'Payment Processing Fees', AccountType::EXPENSE, '5000'],
            ['5200', 'Network Fees', AccountType::EXPENSE, '5000'],
            ['5300', 'Operational Costs', AccountType::EXPENSE, '5000'],
        ];

        $created = [];
        foreach ($accounts as $row) {
            $code = (string) $row[0];
            $name = (string) $row[1];
            $type = $row[2];
            $parentCode = $row[3] !== null ? (string) $row[3] : null;

            $created[$code] = LedgerAccount::firstOrCreate(
                ['code' => $code],
                [
                    'name'        => $name,
                    'type'        => $type,
                    'parent_code' => $parentCode,
                    'currency'    => (string) config('ledger.default_currency', 'USD'),
                    'is_active'   => true,
                ]
            );
        }

        return $created;
    }
}
