<?php

namespace App\Filament\Resources\TokenTransactions\Pages;

use App\Filament\Resources\TokenTransactions\TokenTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTokenTransaction extends ViewRecord
{
    protected static string $resource = TokenTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
