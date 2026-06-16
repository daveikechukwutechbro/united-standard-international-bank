<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExchangeRateResource\Pages;

use App\Filament\Admin\Resources\ExchangeRateResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExchangeRate extends EditRecord
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Rate')
                ->icon('heroicon-m-eye'),

            Actions\DeleteAction::make()
                ->label('Delete Rate')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation(),
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
            ->title('Exchange Rate Updated')
            ->body("Exchange rate {$this->getRecord()->from_asset_code} → {$this->getRecord()->to_asset_code} has been updated.");
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure asset codes are uppercase
        $data['from_asset_code'] = strtoupper($data['from_asset_code']);
        $data['to_asset_code'] = strtoupper($data['to_asset_code']);

        // Add update metadata
        if (! isset($data['metadata'])) {
            $data['metadata'] = [];
        }

        $data['metadata']['updated_by'] = 'admin';
        $data['metadata']['updated_at'] = now()->toISOString();

        return $data;
    }
}
