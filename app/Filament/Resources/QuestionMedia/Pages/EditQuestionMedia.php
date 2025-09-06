<?php

namespace App\Filament\Resources\QuestionMedia\Pages;

use App\Filament\Resources\QuestionMedia\QuestionMediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestionMedia extends EditRecord
{
    protected static string $resource = QuestionMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
