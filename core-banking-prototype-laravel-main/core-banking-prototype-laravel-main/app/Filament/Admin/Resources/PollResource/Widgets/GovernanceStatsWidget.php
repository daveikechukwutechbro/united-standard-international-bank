<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PollResource\Widgets;

use App\Domain\Governance\Enums\PollStatus;
use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GovernanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalPolls = Poll::count();
        $activePolls = Poll::where('status', PollStatus::ACTIVE)->count();
        $totalVotes = Vote::count();
        $totalVotingPower = Vote::sum('voting_power');

        $pollsThisMonth = Poll::where('created_at', '>=', now()->subMonth())->count();
        $pollsLastMonth = Poll::where('created_at', '>=', now()->subMonths(2))
            ->where('created_at', '<', now()->subMonth())
            ->count();

        $pollGrowth = $pollsLastMonth > 0
            ? (($pollsThisMonth - $pollsLastMonth) / $pollsLastMonth) * 100
            : ($pollsThisMonth > 0 ? 100 : 0);

        $votesThisWeek = Vote::where('voted_at', '>=', now()->subWeek())->count();
        $votesLastWeek = Vote::where('voted_at', '>=', now()->subWeeks(2))
            ->where('voted_at', '<', now()->subWeek())
            ->count();

        $voteGrowth = $votesLastWeek > 0
            ? (($votesThisWeek - $votesLastWeek) / $votesLastWeek) * 100
            : ($votesThisWeek > 0 ? 100 : 0);

        $participationRate = $totalPolls > 0
            ? ($totalVotes / max($totalPolls, 1))
            : 0;

        return [
            Stat::make('Total Polls', $totalPolls)
                ->description($pollGrowth >= 0 ? "+{$pollGrowth}% from last month" : "{$pollGrowth}% from last month")
                ->descriptionIcon($pollGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pollGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getPollsChart()),

            Stat::make('Active Polls', $activePolls)
                ->description('Currently accepting votes')
                ->descriptionIcon('heroicon-m-play')
                ->color('warning'),

            Stat::make('Total Votes', number_format($totalVotes))
                ->description($voteGrowth >= 0 ? "+{$voteGrowth}% from last week" : "{$voteGrowth}% from last week")
                ->descriptionIcon($voteGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($voteGrowth >= 0 ? 'success' : 'danger')
                ->chart($this->getVotesChart()),

            Stat::make('Total Voting Power', number_format($totalVotingPower))
                ->description('Cumulative voting power')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info'),

            Stat::make('Avg. Participation', number_format($participationRate, 1) . ' votes/poll')
                ->description('Average votes per poll')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Recent Activity', $votesThisWeek . ' votes this week')
                ->description('Vote activity trend')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }

    private function getPollsChart(): array
    {
        // Get polls created over last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Poll::whereDate('created_at', $date)->count();
            $data[] = $count;
        }

        return $data;
    }

    private function getVotesChart(): array
    {
        // Get votes cast over last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $count = Vote::whereDate('voted_at', $date)->count();
            $data[] = $count;
        }

        return $data;
    }
}
