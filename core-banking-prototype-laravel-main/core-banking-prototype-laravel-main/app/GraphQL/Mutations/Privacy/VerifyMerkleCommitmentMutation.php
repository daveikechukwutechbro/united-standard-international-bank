<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Privacy;

use App\Domain\Privacy\Services\MerkleTreeService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class VerifyMerkleCommitmentMutation
{
    public function __construct(
        private readonly MerkleTreeService $merkleTreeService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): bool
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->merkleTreeService->verifyCommitment(
            $args['network'],
            $args['commitment'],
        );
    }
}
