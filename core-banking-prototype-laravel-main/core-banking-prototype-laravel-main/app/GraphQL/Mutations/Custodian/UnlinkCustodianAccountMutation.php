<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Custodian;

use App\Domain\Custodian\Models\CustodianAccount;
use App\Domain\Custodian\Services\CustodianAccountService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class UnlinkCustodianAccountMutation
{
    public function __construct(
        private readonly CustodianAccountService $custodianAccountService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var CustodianAccount $account */
        $account = CustodianAccount::findOrFail($args['id']);

        $this->custodianAccountService->unlinkAccount($account);

        return true;
    }
}
