<?php

namespace App\Filament\Resources\AssessmentSections\Pages;

use App\Filament\Resources\AssessmentSections\AssessmentSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssessmentSections extends ListRecords
{
    protected static string $resource = AssessmentSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add New'),
        ];
    }
}
