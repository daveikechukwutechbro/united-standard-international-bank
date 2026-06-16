<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Relayer;

use App\Domain\Relayer\Services\SmartAccountService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class SupportedNetworksQuery
{
    public function __construct(
        private readonly SmartAccountService $smartAccountService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->smartAccountService->getSupportedNetworks();
    }
}
