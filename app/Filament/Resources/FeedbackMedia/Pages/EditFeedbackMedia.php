<?php

namespace App\Filament\Resources\FeedbackMedia\Pages;

use App\Filament\Resources\FeedbackMedia\FeedbackMediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedbackMedia extends EditRecord
{
    protected static string $resource = FeedbackMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
