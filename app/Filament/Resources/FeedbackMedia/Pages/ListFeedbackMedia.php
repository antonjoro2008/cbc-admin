<?php

namespace App\Filament\Resources\FeedbackMedia\Pages;

use App\Filament\Resources\FeedbackMedia\FeedbackMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeedbackMedia extends ListRecords
{
    protected static string $resource = FeedbackMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}