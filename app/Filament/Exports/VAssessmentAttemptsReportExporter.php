<?php

namespace App\Filament\Exports;

use App\Models\VAssessmentAttemptsReport;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class VAssessmentAttemptsReportExporter extends Exporter
{
    protected static ?string $model = VAssessmentAttemptsReport::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student_name')
                ->label('Student Name'),
            ExportColumn::make('assessment_name')
                ->label('Assessment Name'),
            // ExportColumn::make('attempt1_score')
            //     ->label('Attempt 1 Score'),
            ExportColumn::make('attempt1_percentage')
                ->label('Attempt 1 %'),
            // ExportColumn::make('attempt2_score')
            //     ->label('Attempt 2 Score'),
            ExportColumn::make('attempt2_percentage')
                ->label('Attempt 2 %'),
            // ExportColumn::make('attempt3_score'),
            // ExportColumn::make('attempt3_percentage'),
            ExportColumn::make('average_score')
                ->label('Average Score'),
            ExportColumn::make('average_percentage')
                ->label('Average %'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your v assessment attempts report export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}