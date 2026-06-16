<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AnomalyDetectionResource\Pages;

use App\Filament\Admin\Resources\AnomalyDetectionResource;
use Filament\Resources\Pages\ListRecords;

class ListAnomalyDetections extends ListRecords
{
    protected static string $resource = AnomalyDetectionResource::class;
}
