<?php

namespace App\Filament\Resources\CoopPayments\Pages;

use App\Filament\Resources\CoopPayments\CoopPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListCoopPayments extends ListRecords
{
    protected static string $resource = CoopPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
