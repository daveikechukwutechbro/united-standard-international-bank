<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\OpenBanking;

use App\Domain\OpenBanking\Services\ConsentService;

final class ConsentQuery
{
    public function __construct(
        private readonly ConsentService $consentService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>|null
     */
    public function __invoke(mixed $rootValue, array $args): ?array
    {
        $consent = $this->consentService->getConsent($args['id']);

        return $consent?->toArray();
    }
}
