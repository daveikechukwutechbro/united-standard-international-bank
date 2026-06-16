<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Privacy;

use App\Domain\Privacy\Services\MerkleTreeService;

final class MerkleSupportedNetworksQuery
{
    public function __construct(
        private readonly MerkleTreeService $merkleTreeService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->merkleTreeService->getSupportedNetworks();
    }
}
