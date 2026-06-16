<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PollResource\Pages;

use App\Filament\Admin\Resources\PollResource;
use App\Filament\Admin\Resources\PollResource\Widgets\GovernanceStatsWidget;
use App\Filament\Admin\Resources\PollResource\Widgets\PollActivityChartWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolls extends ListRecords
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GovernanceStatsWidget::class,
            PollActivityChartWidget::class,
        ];
    }
}
