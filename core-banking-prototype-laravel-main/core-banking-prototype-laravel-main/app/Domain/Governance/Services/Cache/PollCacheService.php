<?php

declare(strict_types=1);

namespace App\Domain\Governance\Services\Cache;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\ValueObjects\PollResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PollCacheService
{
    private const CACHE_PREFIX = 'poll';

    private const POLL_TTL = 1800; // 30 minutes

    private const RESULTS_TTL = 3600; // 1 hour

    private const ACTIVE_POLLS_TTL = 300; // 5 minutes

    private const USER_VOTES_TTL = 1800; // 30 minutes

    public function getPoll(string $uuid): ?Poll
    {
        return Cache::remember(
            $this->getPollKey($uuid),
            self::POLL_TTL,
            fn () => Poll::where('uuid', $uuid)->with(['creator', 'votes'])->first()
        );
    }

    public function cachePoll(Poll $poll): void
    {
        Cache::put($this->getPollKey($poll->uuid), $poll, self::POLL_TTL);
    }

    public function forgetPoll(string $uuid): void
    {
        Cache::forget($this->getPollKey($uuid));
    }

    public function getPollResults(string $pollUuid): ?PollResult
    {
        return Cache::remember(
            $this->getResultsKey($pollUuid),
            self::RESULTS_TTL,
            function () use ($pollUuid) {
                /** @var \Illuminate\Database\Eloquent\Model|null $poll */
                $poll = Poll::where('uuid', $pollUuid)->with('votes')->first();

                return $poll ? $poll->calculateResults() : null;
            }
        );
    }

    public function cachePollResults(string $pollUuid, PollResult $results): void
    {
        Cache::put($this->getResultsKey($pollUuid), $results, self::RESULTS_TTL);
    }

    public function forgetPollResults(string $pollUuid): void
    {
        Cache::forget($this->getResultsKey($pollUuid));
    }

    public function getActivePolls(): Collection
    {
        return Cache::remember(
            $this->getActivePollsKey(),
            self::ACTIVE_POLLS_TTL,
            fn () => Poll::active()->with(['creator', 'votes'])->get()
        );
    }

    public function forgetActivePolls(): void
    {
        Cache::forget($this->getActivePollsKey());
    }

    public function getUserVotingPower(string $userUuid, string $pollUuid): ?int
    {
        return Cache::remember(
            $this->getUserVotingPowerKey($userUuid, $pollUuid),
            self::USER_VOTES_TTL,
            function () use ($userUuid, $pollUuid) {
                /** @var \Illuminate\Database\Eloquent\Model|null $poll */
                $poll = Poll::where('uuid', $pollUuid)->first();
                if (! $poll) {
                    return null;
                }

                /** @var \Illuminate\Database\Eloquent\Model|null $user */
                $user = \App\Models\User::where('uuid', $userUuid)->first();
                if (! $user) {
                    return null;
                }

                $service = app(\App\Domain\Governance\Services\GovernanceService::class);

                return $service->getUserVotingPower($user, $poll);
            }
        );
    }

    public function cacheUserVotingPower(string $userUuid, string $pollUuid, int $power): void
    {
        Cache::put(
            $this->getUserVotingPowerKey($userUuid, $pollUuid),
            $power,
            self::USER_VOTES_TTL
        );
    }

    public function forgetUserVotingPower(string $userUuid, string $pollUuid): void
    {
        Cache::forget($this->getUserVotingPowerKey($userUuid, $pollUuid));
    }

    public function hasUserVoted(string $userUuid, string $pollUuid): bool
    {
        return Cache::remember(
            $this->getUserVoteStatusKey($userUuid, $pollUuid),
            self::USER_VOTES_TTL,
            function () use ($userUuid, $pollUuid) {
                /** @var \Illuminate\Database\Eloquent\Model|null $poll */
                $poll = Poll::where('uuid', $pollUuid)->first();

                return $poll ? $poll->hasUserVoted($userUuid) : false;
            }
        );
    }

    public function cacheUserVoteStatus(string $userUuid, string $pollUuid, bool $hasVoted): void
    {
        Cache::put(
            $this->getUserVoteStatusKey($userUuid, $pollUuid),
            $hasVoted,
            self::USER_VOTES_TTL
        );
    }

    public function forgetUserVoteStatus(string $userUuid, string $pollUuid): void
    {
        Cache::forget($this->getUserVoteStatusKey($userUuid, $pollUuid));
    }

    public function invalidatePollCache(string $pollUuid): void
    {
        $this->forgetPoll($pollUuid);
        $this->forgetPollResults($pollUuid);
        $this->forgetActivePolls();

        // Note: We don't invalidate user-specific caches here as they might be expensive to recalculate
        // They have their own TTL and will expire naturally
    }

    public function invalidateUserPollCache(string $userUuid, string $pollUuid): void
    {
        $this->forgetUserVotingPower($userUuid, $pollUuid);
        $this->forgetUserVoteStatus($userUuid, $pollUuid);
    }

    public function warmupActivePolls(): Collection
    {
        // Warm up the active polls cache
        $polls = $this->getActivePolls();

        // Optionally warm up results for active polls
        foreach ($polls as $poll) {
            $this->getPollResults($poll->uuid);
        }

        return $polls;
    }

    public function getStats(): array
    {
        $keys = [
            'polls'             => $this->getPollKey('*'),
            'results'           => $this->getResultsKey('*'),
            'user_voting_power' => $this->getUserVotingPowerKey('*', '*'),
            'user_vote_status'  => $this->getUserVoteStatusKey('*', '*'),
        ];

        $stats = [];
        foreach ($keys as $type => $pattern) {
            $cacheKeys = Cache::getRedis()->keys($pattern);
            $stats[$type] = count($cacheKeys);
        }

        $stats['active_polls_cached'] = Cache::has($this->getActivePollsKey());

        return $stats;
    }

    private function getPollKey(string $uuid): string
    {
        return sprintf('%s:poll:%s', self::CACHE_PREFIX, $uuid);
    }

    private function getResultsKey(string $pollUuid): string
    {
        return sprintf('%s:results:%s', self::CACHE_PREFIX, $pollUuid);
    }

    private function getActivePollsKey(): string
    {
        return sprintf('%s:active_polls', self::CACHE_PREFIX);
    }

    private function getUserVotingPowerKey(string $userUuid, string $pollUuid): string
    {
        return sprintf('%s:user_power:%s:%s', self::CACHE_PREFIX, $userUuid, $pollUuid);
    }

    private function getUserVoteStatusKey(string $userUuid, string $pollUuid): string
    {
        return sprintf('%s:user_voted:%s:%s', self::CACHE_PREFIX, $userUuid, $pollUuid);
    }
}
