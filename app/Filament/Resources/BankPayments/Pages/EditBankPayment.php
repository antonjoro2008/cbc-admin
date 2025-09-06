<?php

namespace App\Filament\Resources\BankPayments\Pages;

use App\Filament\Resources\BankPayments\BankPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankPayment extends EditRecord
{
    protected static string $resource = BankPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
