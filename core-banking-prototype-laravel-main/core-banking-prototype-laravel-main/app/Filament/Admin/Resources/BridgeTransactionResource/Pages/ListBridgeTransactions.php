<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BridgeTransactionResource\Pages;

use App\Filament\Admin\Resources\BridgeTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListBridgeTransactions extends ListRecords
{
    protected static string $resource = BridgeTransactionResource::class;
}
