<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AssetResource\Pages;

use App\Filament\Admin\Resources\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Asset')
                ->icon('heroicon-m-pencil-square'),

            Actions\Action::make('toggle_status')
                ->label(fn () => $this->getRecord()->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn () => $this->getRecord()->is_active ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                ->color(fn () => $this->getRecord()->is_active ? 'danger' : 'success')
                ->action(
                    function () {
                        $record = $this->getRecord();
                        $record->update(['is_active' => ! $record->is_active]);

                        $this->dispatch('$refresh');

                        $status = $record->is_active ? 'activated' : 'deactivated';
                        $this->getSuccessNotification("Asset has been {$status}.");
                    }
                )
                ->requiresConfirmation(fn () => $this->getRecord()->is_active)
                ->modalDescription(
                    fn () => $this->getRecord()->is_active
                    ? 'Are you sure you want to deactivate this asset? This will prevent new transactions.'
                    : null
                ),

            Actions\DeleteAction::make()
                ->label('Delete Asset')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to delete this asset? This action cannot be undone and may affect existing balances.'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AssetResource\Widgets\AssetOverviewWidget::class,
        ];
    }
}
