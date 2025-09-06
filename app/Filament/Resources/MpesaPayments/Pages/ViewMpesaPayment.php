<?php

namespace App\Filament\Resources\MpesaPayments\Pages;

use App\Filament\Resources\MpesaPayments\MpesaPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMpesaPayment extends ViewRecord
{
    protected static string $resource = MpesaPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
