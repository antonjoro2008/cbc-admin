<?php

namespace App\Filament\Resources\TokenUsages\Pages;

use App\Filament\Resources\TokenUsages\TokenUsageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTokenUsages extends ListRecords
{
    protected static string $resource = TokenUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
