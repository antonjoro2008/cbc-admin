<?php

namespace App\Filament\Resources\BankPayments\Pages;

use App\Filament\Resources\BankPayments\BankPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBankPayment extends ViewRecord
{
    protected static string $resource = BankPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
