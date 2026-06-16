<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Interledger;

use App\Domain\Interledger\Services\QuoteService;

class IlpSupportedAssetsQuery
{
    public function __construct(
        private readonly QuoteService $quoteService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array{code: string, scale: int}>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return array_values($this->quoteService->getSupportedAssets());
    }
}
