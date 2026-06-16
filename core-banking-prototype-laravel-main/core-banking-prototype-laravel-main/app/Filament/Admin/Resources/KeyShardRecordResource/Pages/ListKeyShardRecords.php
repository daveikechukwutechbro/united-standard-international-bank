<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KeyShardRecordResource\Pages;

use App\Filament\Admin\Resources\KeyShardRecordResource;
use Filament\Resources\Pages\ListRecords;

class ListKeyShardRecords extends ListRecords
{
    protected static string $resource = KeyShardRecordResource::class;
}
