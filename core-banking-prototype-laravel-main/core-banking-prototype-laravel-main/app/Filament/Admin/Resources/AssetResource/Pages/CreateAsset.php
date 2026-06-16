<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AssetResource\Pages;

use App\Filament\Admin\Resources\AssetResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Asset Created')
            ->body("Asset {$this->getRecord()->code} has been successfully created.");
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure code is uppercase for consistency
        $data['code'] = strtoupper($data['code']);

        // Set default metadata based on asset type
        if (empty($data['metadata'])) {
            $data['metadata'] = match ($data['type']) {
                'fiat' => [
                    'category'  => 'currency',
                    'regulated' => true,
                ],
                'crypto' => [
                    'category'         => 'digital_currency',
                    'blockchain_based' => true,
                ],
                'commodity' => [
                    'category'       => 'physical_asset',
                    'precious_metal' => in_array($data['code'], ['XAU', 'XAG', 'XPT', 'XPD']),
                ],
                default => [],
            };
        }

        return $data;
    }
}
