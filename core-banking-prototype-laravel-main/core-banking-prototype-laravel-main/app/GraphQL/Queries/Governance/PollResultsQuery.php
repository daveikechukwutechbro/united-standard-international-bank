<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Governance;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Services\GovernanceService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class PollResultsQuery
{
    public function __construct(
        private readonly GovernanceService $governanceService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var Poll $poll */
        $poll = Poll::findOrFail($args['poll_id']);
        $results = $this->governanceService->getPollResults($poll);

        return [
            'poll_id'            => $poll->id,
            'total_votes'        => $poll->getVoteCount(),
            'total_voting_power' => $poll->getTotalVotingPower(),
            'results'            => json_encode($results) ?: '{}',
        ];
    }
}
