<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AssetResource\Pages;

use App\Filament\Admin\Resources\AssetResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Asset')
                ->icon('heroicon-m-eye'),

            Actions\DeleteAction::make()
                ->label('Delete Asset')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this asset? This action cannot be undone and may affect existing balances.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asset Updated')
            ->body("Asset {$this->getRecord()->code} has been successfully updated.");
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure code is uppercase for consistency
        $data['code'] = strtoupper($data['code']);

        return $data;
    }
}
