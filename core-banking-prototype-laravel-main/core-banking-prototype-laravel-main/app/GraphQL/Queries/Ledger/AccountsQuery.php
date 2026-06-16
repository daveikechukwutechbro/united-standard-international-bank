<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Ledger;

use App\Domain\Ledger\Services\ChartOfAccountsService;

final class AccountsQuery
{
    public function __construct(
        private readonly ChartOfAccountsService $chartOfAccountsService,
    ) {
    }

    /**
     * Resolve the full chart of accounts.
     *
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->chartOfAccountsService->getAll()->map(fn ($account): array => [
            'code'        => $account->code,
            'name'        => $account->name,
            'type'        => $account->type->value,
            'parent_code' => $account->parent_code,
            'currency'    => $account->currency,
            'is_active'   => $account->is_active,
        ])->values()->all();
    }
}
