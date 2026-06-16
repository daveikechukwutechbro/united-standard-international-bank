<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Microfinance;

use App\Domain\Microfinance\Models\ShareAccount;
use App\Domain\Microfinance\Services\ShareAccountService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class OpenShareAccountMutation
{
    public function __construct(
        private readonly ShareAccountService $shareAccountService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     *
     * @throws AuthenticationException
     */
    public function __invoke(mixed $rootValue, array $args): ShareAccount
    {
        $user = Auth::user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->shareAccountService->openAccount(
            userId: $user->id,
            groupId: isset($args['group_id']) ? (string) $args['group_id'] : null,
            currency: $args['currency'] ?? 'USD',
        );
    }
}
