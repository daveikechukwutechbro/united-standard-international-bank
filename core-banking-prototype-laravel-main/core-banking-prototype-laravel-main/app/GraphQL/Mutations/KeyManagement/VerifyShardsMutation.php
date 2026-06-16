<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\KeyManagement;

use App\Domain\KeyManagement\Models\KeyShardRecord;
use App\Domain\KeyManagement\Services\ShamirService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class VerifyShardsMutation
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

        $shards = KeyShardRecord::query()
            ->where('user_uuid', $args['user_uuid'])
            ->where('key_version', $args['key_version'])
            ->where('status', 'active')
            ->get()
            ->toArray();

        return $this->shamirService->verifyShards($shards, $args['expected_public_key'] ?? '');
    }
}
