<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Governance;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Services\GovernanceService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class CreatePollMutation
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
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $this->governanceService->createPoll([
            'title'                  => $args['title'],
            'description'            => $args['description'] ?? null,
            'type'                   => $args['type'],
            'options'                => json_decode($args['options'], true) ?: [],
            'start_date'             => $args['start_date'],
            'end_date'               => $args['end_date'],
            'required_participation' => $args['required_participation'] ?? null,
            'voting_power_strategy'  => $args['voting_power_strategy'] ?? 'one_user_one_vote',
            'created_by'             => $user->uuid,
        ]);
    }
}
