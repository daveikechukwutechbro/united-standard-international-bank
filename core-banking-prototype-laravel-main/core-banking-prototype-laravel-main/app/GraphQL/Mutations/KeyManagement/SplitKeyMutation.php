<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\KeyManagement;

use App\Domain\KeyManagement\Services\ShamirService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class SplitKeyMutation
{
    public function __construct(
        private readonly ShamirService $shamirService,
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

        $shards = $this->shamirService->splitKey($args['secret'], (string) $user->id);

        return count($shards) > 0;
    }
}
