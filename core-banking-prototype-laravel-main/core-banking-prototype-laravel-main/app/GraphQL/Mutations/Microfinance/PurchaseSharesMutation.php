<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Microfinance;

use App\Domain\Microfinance\Models\ShareAccount;
use App\Domain\Microfinance\Services\ShareAccountService;

class PurchaseSharesMutation
{
    public function __construct(
        private readonly ShareAccountService $shareAccountService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): ShareAccount
    {
        return $this->shareAccountService->purchaseShares(
            accountId: (string) $args['account_id'],
            shares: (int) $args['shares'],
        );
    }
}
