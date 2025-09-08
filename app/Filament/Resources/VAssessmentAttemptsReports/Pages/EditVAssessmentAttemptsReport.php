<?php

namespace App\Filament\Resources\VAssessmentAttemptsReports\Pages;

use App\Filament\Resources\VAssessmentAttemptsReports\VAssessmentAttemptsReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVAssessmentAttemptsReport extends EditRecord
{
    protected static string $resource = VAssessmentAttemptsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
