<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExchangeRateResource\Pages;

use App\Filament\Admin\Resources\ExchangeRateResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExchangeRate extends CreateRecord
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Exchange Rate Created')
            ->body("Exchange rate {$this->getRecord()->from_asset_code} → {$this->getRecord()->to_asset_code} has been created.");
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure asset codes are uppercase
        $data['from_asset_code'] = strtoupper($data['from_asset_code']);
        $data['to_asset_code'] = strtoupper($data['to_asset_code']);

        // Add creation metadata
        if (empty($data['metadata'])) {
            $data['metadata'] = [];
        }

        $data['metadata']['created_by'] = 'admin';
        $data['metadata']['created_at'] = now()->toISOString();

        return $data;
    }
}
