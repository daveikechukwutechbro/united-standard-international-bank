<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExchangeRateResource\Pages;

use App\Filament\Admin\Resources\ExchangeRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExchangeRate extends ViewRecord
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Rate')
                ->icon('heroicon-m-pencil-square'),

            Actions\Action::make('refresh')
                ->label('Refresh Rate')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->action(
                    function () {
                        $this->getRecord()->update(['valid_at' => now()]);
                        $this->dispatch('$refresh');
                    }
                )
                ->requiresConfirmation()
                ->visible(fn () => $this->getRecord()->source !== 'manual'),

            Actions\Action::make('toggle_status')
                ->label(fn () => $this->getRecord()->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn () => $this->getRecord()->is_active ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                ->color(fn () => $this->getRecord()->is_active ? 'danger' : 'success')
                ->action(
                    function () {
                        $record = $this->getRecord();
                        $record->update(['is_active' => ! $record->is_active]);
                        $this->dispatch('$refresh');
                    }
                )
                ->requiresConfirmation(fn () => $this->getRecord()->is_active),

            Actions\DeleteAction::make()
                ->label('Delete Rate')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation(),
        ];
    }
}
