<?php

namespace App\Filament\Resources\FeedbackMedia\Pages;

use App\Filament\Resources\FeedbackMedia\FeedbackMediaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedbackMedia extends ViewRecord
{
    protected static string $resource = FeedbackMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
