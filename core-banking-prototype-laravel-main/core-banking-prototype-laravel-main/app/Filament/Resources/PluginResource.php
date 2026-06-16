<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Shared\Models\Plugin;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PluginResource extends Resource
{
    protected static ?string $model = Plugin::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Plugins';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\IconColumn::make('is_system')
                    ->boolean()
                    ->label('System'),
                Tables\Columns\TextColumn::make('installed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                        'failed'   => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => PluginResource\Pages\ListPlugins::route('/'),
        ];
    }
}
