<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Relayer;

use App\Domain\Relayer\Models\SmartAccount;
use App\Domain\Relayer\Services\SmartAccountService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class CreateSmartAccountMutation
{
    public function __construct(
        private readonly SmartAccountService $smartAccountService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): SmartAccount
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->smartAccountService->getOrCreateAccount(
            $user,
            $args['owner_address'],
            $args['network'],
        );
    }
}
