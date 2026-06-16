<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Banking;

use App\Domain\Banking\Services\BankIntegrationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class AvailableBanksQuery
{
    public function __construct(
        private readonly BankIntegrationService $bankIntegrationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->bankIntegrationService->getAvailableConnectors()->toArray();
    }
}
