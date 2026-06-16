<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentIntentResource\Pages;

use App\Filament\Admin\Resources\PaymentIntentResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentIntents extends ListRecords
{
    protected static string $resource = PaymentIntentResource::class;
}
