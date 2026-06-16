<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Ledger;

use App\Domain\Ledger\Services\LedgerService;

final class TrialBalanceQuery
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {
    }

    /**
     * Resolve the full trial balance.
     *
     * @param  array<string, mixed>  $args
     * @return array<int, array{account_code: string, debit: string, credit: string, balance: string}>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $trialBalance = $this->ledgerService->getTrialBalance();

        $result = [];
        foreach ($trialBalance as $accountCode => $row) {
            $result[] = [
                'account_code' => (string) $accountCode,
                'debit'        => (string) $row['debit'],
                'credit'       => (string) $row['credit'],
                'balance'      => (string) $row['balance'],
            ];
        }

        return $result;
    }
}
