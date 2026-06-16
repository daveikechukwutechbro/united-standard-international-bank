<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Account;

use App\Domain\Account\Models\Account;

class AccountQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Account
    {
        /** @var Account */
        return Account::findOrFail($args['id']);
    }
}
