<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PortfolioSnapshotResource\Pages;

use App\Filament\Admin\Resources\PortfolioSnapshotResource;
use Filament\Resources\Pages\ListRecords;

class ListPortfolioSnapshots extends ListRecords
{
    protected static string $resource = PortfolioSnapshotResource::class;
}
