<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Governance;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Services\GovernanceService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class CancelPollMutation
{
    public function __construct(
        private readonly GovernanceService $governanceService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Poll
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var Poll $poll */
        $poll = Poll::findOrFail($args['id']);

        $this->governanceService->cancelPoll($poll, $args['reason'] ?? null);

        return $poll->refresh();
    }
}
