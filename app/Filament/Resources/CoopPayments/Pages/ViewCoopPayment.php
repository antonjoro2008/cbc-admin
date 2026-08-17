<?php

namespace App\Filament\Resources\CoopPayments\Pages;

use App\Filament\Resources\CoopPayments\CoopPaymentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCoopPayment extends ViewRecord
{
    protected static string $resource = CoopPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
