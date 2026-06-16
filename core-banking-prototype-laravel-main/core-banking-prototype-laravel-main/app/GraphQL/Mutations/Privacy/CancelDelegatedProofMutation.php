<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Privacy;

use App\Domain\Privacy\Models\DelegatedProofJob;
use App\Domain\Privacy\Services\DelegatedProofService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class CancelDelegatedProofMutation
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

        $this->delegatedProofService->cancelJob($user, $args['id']);

        /** @var DelegatedProofJob */
        return DelegatedProofJob::findOrFail($args['id']);
    }
}
