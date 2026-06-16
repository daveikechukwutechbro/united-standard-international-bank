<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FilingScheduleResource\Pages;

use App\Filament\Admin\Resources\FilingScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFilingSchedule extends ViewRecord
{
    protected static string $resource = FilingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
