<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\ISO20022;

use App\Domain\ISO20022\Services\MessageRegistry;

final class SupportedTypesQuery
{
    public function __construct(
        private readonly MessageRegistry $registry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->registry->supportedTypes();
    }
}
