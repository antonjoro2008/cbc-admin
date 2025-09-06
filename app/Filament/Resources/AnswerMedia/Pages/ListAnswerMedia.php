<?php

namespace App\Filament\Resources\AnswerMedia\Pages;

use App\Filament\Resources\AnswerMedia\AnswerMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnswerMedia extends ListRecords
{
    protected static string $resource = AnswerMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add New'),
        ];
    }
}
