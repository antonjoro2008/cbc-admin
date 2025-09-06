<?php

namespace App\Filament\Resources\TokenTransactions\Pages;

use App\Filament\Resources\TokenTransactions\TokenTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTokenTransaction extends EditRecord
{
    protected static string $resource = TokenTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
