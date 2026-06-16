<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\DeFiPositionResource\Pages;

use App\Filament\Admin\Resources\DeFiPositionResource;
use Filament\Resources\Pages\ListRecords;

class ListDeFiPositions extends ListRecords
{
    protected static string $resource = DeFiPositionResource::class;
}
