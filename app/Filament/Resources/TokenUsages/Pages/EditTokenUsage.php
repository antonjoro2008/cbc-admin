<?php

namespace App\Filament\Resources\TokenUsages\Pages;

use App\Filament\Resources\TokenUsages\TokenUsageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTokenUsage extends EditRecord
{
    protected static string $resource = TokenUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
