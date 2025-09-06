<?php

namespace App\Filament\Resources\MpesaPayments\Pages;

use App\Filament\Resources\MpesaPayments\MpesaPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMpesaPayment extends EditRecord
{
    protected static string $resource = MpesaPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
