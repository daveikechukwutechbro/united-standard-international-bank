<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Ledger;

use App\Domain\Ledger\Enums\AccountType;
use App\Domain\Ledger\Models\LedgerAccount;
use App\Domain\Ledger\Services\ChartOfAccountsService;

final class CreateAccountMutation
{
    public function __construct(
        private readonly ChartOfAccountsService $chartOfAccountsService,
    ) {
    }

    /**
     * Create a new ledger account.
     *
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): LedgerAccount
    {
        return $this->chartOfAccountsService->createAccount(
            code: (string) $args['code'],
            name: (string) $args['name'],
            type: AccountType::from((string) $args['type']),
            parentCode: isset($args['parent_code']) ? (string) $args['parent_code'] : null,
            currency: isset($args['currency']) ? (string) $args['currency'] : 'USD',
            description: isset($args['description']) ? (string) $args['description'] : null,
        );
    }
}
