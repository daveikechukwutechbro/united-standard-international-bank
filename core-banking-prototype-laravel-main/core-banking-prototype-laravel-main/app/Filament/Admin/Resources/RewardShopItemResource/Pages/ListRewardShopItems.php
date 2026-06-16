<?php

namespace App\Filament\Admin\Resources\RewardShopItemResource\Pages;

use App\Filament\Admin\Resources\RewardShopItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRewardShopItems extends ListRecords
{
    protected static string $resource = RewardShopItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
