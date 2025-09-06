<?php

namespace App\Filament\Resources\TokenTransactions\Pages;

use App\Filament\Resources\TokenTransactions\TokenTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTokenTransactions extends ListRecords
{
    protected static string $resource = TokenTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
