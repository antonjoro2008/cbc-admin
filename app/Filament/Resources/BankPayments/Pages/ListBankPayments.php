<?php

namespace App\Filament\Resources\BankPayments\Pages;

use App\Filament\Resources\BankPayments\BankPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankPayments extends ListRecords
{
    protected static string $resource = BankPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
