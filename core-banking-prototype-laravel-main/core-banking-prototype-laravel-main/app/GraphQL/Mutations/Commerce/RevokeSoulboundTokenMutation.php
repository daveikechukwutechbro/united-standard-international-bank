<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Commerce;

use App\Domain\Commerce\Services\SoulboundTokenService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class RevokeSoulboundTokenMutation
{
    public function __construct(
        private readonly SoulboundTokenService $soulboundTokenService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): bool
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $this->soulboundTokenService->revokeToken($args['token_hash'], $args['reason']);

        return true;
    }
}
