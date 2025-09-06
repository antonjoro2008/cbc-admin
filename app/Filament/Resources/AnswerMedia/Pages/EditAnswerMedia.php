<?php

namespace App\Filament\Resources\AnswerMedia\Pages;

use App\Filament\Resources\AnswerMedia\AnswerMediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnswerMedia extends EditRecord
{
    protected static string $resource = AnswerMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
