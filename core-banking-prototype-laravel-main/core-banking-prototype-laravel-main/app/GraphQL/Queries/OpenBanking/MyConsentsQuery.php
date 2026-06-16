<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\OpenBanking;

use App\Domain\OpenBanking\Services\ConsentService;
use Illuminate\Support\Facades\Auth;

final class MyConsentsQuery
{
    public function __construct(
        private readonly ConsentService $consentService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->consentService->getActiveConsentsForUser((int) Auth::id())->toArray();
    }
}
