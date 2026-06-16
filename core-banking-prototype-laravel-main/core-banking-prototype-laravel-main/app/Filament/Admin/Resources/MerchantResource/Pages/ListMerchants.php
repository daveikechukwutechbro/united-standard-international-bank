<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MerchantResource\Pages;

use App\Filament\Admin\Resources\MerchantResource;
use Filament\Resources\Pages\ListRecords;

class ListMerchants extends ListRecords
{
    protected static string $resource = MerchantResource::class;
}
