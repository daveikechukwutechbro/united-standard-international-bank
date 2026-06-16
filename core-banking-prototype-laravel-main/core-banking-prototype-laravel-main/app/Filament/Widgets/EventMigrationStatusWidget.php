<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Monitoring\Models\EventMigration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EventMigrationStatusWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $total = EventMigration::count();
        $completed = EventMigration::where('status', 'completed')->count();
        $running = EventMigration::where('status', 'running')->count();
        $failed = EventMigration::where('status', 'failed')->count();

        $totalEventsMigrated = EventMigration::where('status', 'completed')
            ->sum('events_migrated');

        return [
            Stat::make('Total Migrations', $total)
                ->description("{$completed} completed, {$running} running")
                ->color('primary'),

            Stat::make('Events Migrated', number_format((int) $totalEventsMigrated))
                ->description('Across all completed migrations')
                ->color('success'),

            Stat::make('Failed Migrations', $failed)
                ->description($failed > 0 ? 'Requires attention' : 'All healthy')
                ->color($failed > 0 ? 'danger' : 'success'),
        ];
    }
}
