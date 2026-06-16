<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\DelegatedProofJobResource\Pages;

use App\Filament\Admin\Resources\DelegatedProofJobResource;
use Filament\Resources\Pages\ListRecords;

class ListDelegatedProofJobs extends ListRecords
{
    protected static string $resource = DelegatedProofJobResource::class;
}
