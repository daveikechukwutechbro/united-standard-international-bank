<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PollResource\Pages;

use App\Filament\Admin\Resources\PollResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set the created_by to the current admin user
        $data['created_by'] = auth()->user()->uuid;

        return $data;
    }
}
