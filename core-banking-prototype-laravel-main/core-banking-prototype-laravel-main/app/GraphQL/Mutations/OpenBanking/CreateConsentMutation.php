<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\OpenBanking;

use App\Domain\OpenBanking\Services\ConsentService;
use Illuminate\Support\Facades\Auth;

final class CreateConsentMutation
{
    public function __construct(
        private readonly ConsentService $consentService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $consent = $this->consentService->createConsent(
            $args['tpp_id'],
            (int) Auth::id(),
            $args['permissions'],
            $args['account_ids'] ?? null,
        );

        return $consent->toArray();
    }
}
