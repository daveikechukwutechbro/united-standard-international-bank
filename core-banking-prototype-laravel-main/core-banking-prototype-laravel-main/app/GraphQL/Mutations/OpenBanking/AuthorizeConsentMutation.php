<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\OpenBanking;

use App\Domain\OpenBanking\Services\ConsentService;
use Illuminate\Support\Facades\Auth;

final class AuthorizeConsentMutation
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
        $consent = $this->consentService->authorizeConsent($args['id'], (int) Auth::id());

        return $consent->toArray();
    }
}
