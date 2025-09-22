<?php

namespace App\Filament\Resources\QuestionMedia\Pages;

use App\Filament\Resources\QuestionMedia\QuestionMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestionMedia extends ListRecords
{
    protected static string $resource = QuestionMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add New'),
        ];
    }
}
