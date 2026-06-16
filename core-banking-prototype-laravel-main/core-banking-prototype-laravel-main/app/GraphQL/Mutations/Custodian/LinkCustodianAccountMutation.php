<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Custodian;

use App\Domain\Account\Models\Account;
use App\Domain\Custodian\Models\CustodianAccount;
use App\Domain\Custodian\Services\CustodianAccountService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class LinkCustodianAccountMutation
{
    public function __construct(
        private readonly CustodianAccountService $custodianAccountService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): CustodianAccount
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var Account $account */
        $account = Account::query()
            ->where('uuid', $args['account_uuid'])
            ->firstOrFail();

        return $this->custodianAccountService->linkAccount(
            $account,
            $args['custodian_name'],
            $args['custodian_account_id'],
        );
    }
}
