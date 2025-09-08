<?php

namespace App\Filament\Resources\VAssessmentAttemptsReports\Pages;

use App\Filament\Resources\VAssessmentAttemptsReports\VAssessmentAttemptsReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVAssessmentAttemptsReports extends ListRecords
{
    protected static string $resource = VAssessmentAttemptsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
