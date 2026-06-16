<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MultiSigWalletResource\Pages;

use App\Filament\Admin\Resources\MultiSigWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListMultiSigWallets extends ListRecords
{
    protected static string $resource = MultiSigWalletResource::class;
}
