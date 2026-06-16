<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class EventStoreDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Event Store Dashboard';

    protected static string $view = 'filament.admin.pages.event-store-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\EventStoreStatsWidget::class,
            \App\Filament\Admin\Widgets\EventStoreThroughputWidget::class,
            \App\Filament\Admin\Widgets\AggregateHealthWidget::class,
            \App\Filament\Admin\Widgets\SystemMetricsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 2;
    }
}
