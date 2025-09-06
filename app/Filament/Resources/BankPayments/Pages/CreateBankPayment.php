<?php

namespace App\Filament\Resources\BankPayments\Pages;

use App\Filament\Resources\BankPayments\BankPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankPayment extends CreateRecord
{
    protected static string $resource = BankPaymentResource::class;
}
