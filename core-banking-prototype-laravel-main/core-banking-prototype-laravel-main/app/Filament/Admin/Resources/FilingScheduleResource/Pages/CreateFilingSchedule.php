<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FilingScheduleResource\Pages;

use App\Filament\Admin\Resources\FilingScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFilingSchedule extends CreateRecord
{
    protected static string $resource = FilingScheduleResource::class;
}
