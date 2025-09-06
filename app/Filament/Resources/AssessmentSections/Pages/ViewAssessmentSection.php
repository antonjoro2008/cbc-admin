<?php

namespace App\Filament\Resources\AssessmentSections\Pages;

use App\Filament\Resources\AssessmentSections\AssessmentSectionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssessmentSection extends ViewRecord
{
    protected static string $resource = AssessmentSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
