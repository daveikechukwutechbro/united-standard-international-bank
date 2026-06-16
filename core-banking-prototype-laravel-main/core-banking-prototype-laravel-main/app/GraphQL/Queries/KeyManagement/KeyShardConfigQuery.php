<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\KeyManagement;

use App\Domain\KeyManagement\Services\ShamirService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class KeyShardConfigQuery
{
    public function __construct(
        private readonly ShamirService $shamirService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, int>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return [
            'threshold'    => $this->shamirService->getThreshold(),
            'total_shards' => $this->shamirService->getTotalShards(),
        ];
    }
}
