<?php

namespace App\Filament\Resources\VAssessmentAttemptsReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use App\Filament\Exports\VAssessmentAttemptsReportExporter;
use Filament\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Models\VAssessmentAttemptsReport;


class VAssessmentAttemptsReportsTable
{

    public static function configure(Table $table): Table
    {
        $maxAttempts = (int) DB::table('settings')
            ->where('key_name', 'max_number_of_assessment_attempts')
            ->value('value') ?? 1;

        $columns = [
            TextColumn::make('student_name')->sortable()->searchable(),
            TextColumn::make('assessment_name')->sortable()->searchable(),
        ];

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $columns[] = TextColumn::make("attempt{$i}_score")->label("Attempt {$i} Score")->numeric();
            $columns[] = TextColumn::make("attempt{$i}_percentage")->label("Attempt {$i} %")->numeric();
        }

        $columns[] = TextColumn::make('average_score')->label('Avg Score')->numeric();
        $columns[] = TextColumn::make('average_percentage')->label('Avg %')->numeric();

        return $table
            ->query(VAssessmentAttemptsReport::query())
            ->columns($columns)
            ->filters([
                SelectFilter::make('student_name')
                    ->label('Student')
                    ->options(function () {
                        return VAssessmentAttemptsReport::distinct()
                            ->pluck('student_name', 'student_name')
                            ->sort()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                
                SelectFilter::make('assessment_name')
                    ->label('Assessment')
                    ->options(function () {
                        return VAssessmentAttemptsReport::distinct()
                            ->pluck('assessment_name', 'assessment_name')
                            ->sort()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(VAssessmentAttemptsReportExporter::class),
            ])
            ->paginated();
    }
}
