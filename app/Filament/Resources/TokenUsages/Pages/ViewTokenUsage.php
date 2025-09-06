<?php

namespace App\Filament\Resources\TokenUsages\Pages;

use App\Filament\Resources\TokenUsages\TokenUsageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTokenUsage extends ViewRecord
{
    protected static string $resource = TokenUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
