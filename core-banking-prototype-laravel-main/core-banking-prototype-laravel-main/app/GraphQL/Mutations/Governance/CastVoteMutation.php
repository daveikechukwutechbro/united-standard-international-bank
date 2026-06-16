<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Governance;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Domain\Governance\Services\GovernanceService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class CastVoteMutation
{
    public function __construct(
        private readonly GovernanceService $governanceService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Vote
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var Poll $poll */
        $poll = Poll::findOrFail($args['poll_id']);
        $selectedOptions = json_decode($args['selected_options'], true) ?: [];

        return $this->governanceService->castVote($poll, $user, $selectedOptions);
    }
}
