<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Privacy;

use App\Domain\Privacy\Models\DelegatedProofJob;
use App\Domain\Privacy\Services\DelegatedProofService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class RequestDelegatedProofMutation
{
    public function __construct(
        private readonly DelegatedProofService $delegatedProofService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): DelegatedProofJob
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->delegatedProofService->requestProof(
            user: $user,
            proofType: $args['proof_type'],
            network: $args['network'],
            publicInputs: json_decode($args['public_inputs'], true) ?: [],
            encryptedPrivateInputs: $args['encrypted_private_inputs'],
        );
    }
}
