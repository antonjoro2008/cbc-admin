<?php

namespace App\Filament\Resources\MpesaPayments\Pages;

use App\Filament\Resources\MpesaPayments\MpesaPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMpesaPayments extends ListRecords
{
    protected static string $resource = MpesaPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
