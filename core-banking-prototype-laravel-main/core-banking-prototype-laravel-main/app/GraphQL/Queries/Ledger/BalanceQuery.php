<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Ledger;

use App\Domain\Ledger\Services\LedgerService;

final class BalanceQuery
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {
    }

    /**
     * Resolve the balance for a specific account.
     *
     * @param  array<string, mixed>  $args
     * @return array{amount: string, currency: string}
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->ledgerService->getBalance((string) $args['account_code']);
    }
}
