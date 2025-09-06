<?php

namespace App\Filament\Resources\AssessmentSections\Pages;

use App\Filament\Resources\AssessmentSections\AssessmentSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssessmentSection extends EditRecord
{
    protected static string $resource = AssessmentSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
