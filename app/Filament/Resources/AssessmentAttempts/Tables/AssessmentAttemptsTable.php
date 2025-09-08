<?php

namespace App\Filament\Resources\AssessmentAttempts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkAction;
use Filament\Tables\Table;
use App\Models\Assessment;
use App\Models\User;
use App\Models\Institution;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class AssessmentAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assessment.title')
                    ->label('Assessment')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attempt_number')
                    ->label('Attempt Number')
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Score')
                    ->sortable(),
                TextColumn::make('total_marks')
                    ->label('Total Marks (Auto-marked)')
                    ->getStateUsing(function ($record) {
                        // Get total marks for auto-marked questions only
                        $totalMarks = $record->assessment->questions()
                            ->whereIn('question_type', ['mcq', 'true_false', 'matching', 'fill_blank'])
                            ->sum('marks');
                        
                        return $totalMarks ?: 'N/A';
                    })
                    ->sortable(false), // Can't sort computed columns
                TextColumn::make('started_at')
                    ->label('Started At')
                    ->dateTime('d/m/Y H:iA')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d/m/Y H:iA')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('attempt_number')
                    ->label('Attempt Number')
                    ->options([
                        1 => 'Attempt 1',
                        2 => 'Attempt 2',
                        3 => 'Attempt 3',
                    ])
                    ->multiple(),
                
                SelectFilter::make('assessment_id')
                    ->label('Assessment')
                    ->relationship('assessment', 'title')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                
                SelectFilter::make('institution_id')
                    ->label('Institution')
                    ->relationship('student.institution', 'name')
                    ->searchable()
                    ->preload(),
                
                Filter::make('completed')
                    ->label('Completed Attempts')
                    ->query(fn ($query) => $query->whereNotNull('completed_at')),
                
                Filter::make('in_progress')
                    ->label('In Progress Attempts')
                    ->query(fn ($query) => $query->whereNull('completed_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_csv')
                        ->label('Export to CSV')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Collection $records) {
                            return self::exportToCSV($records);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * Export records to CSV format
     */
    public static function exportToCSV(Collection $records)
    {
        $filename = 'assessment_attempts_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $data = $records->map(function ($record) {
            // Calculate total marks for auto-marked questions only
            $totalMarks = $record->assessment->questions()
                ->whereIn('question_type', ['mcq', 'true_false', 'matching', 'fill_blank'])
                ->sum('marks');
            
            return [
                'Student Name' => $record->student->name ?? 'N/A',
                'Student Email' => $record->student->email ?? 'N/A',
                'Institution' => $record->student->institution->name ?? 'N/A',
                'Assessment Title' => $record->assessment->title ?? 'N/A',
                'Attempt Number' => $record->attempt_number ?? 'N/A',
                'Score' => $record->score ?? 'N/A',
                'Total Marks (Auto-marked)' => $totalMarks ?: 'N/A',
                'Started At' => $record->started_at ? $record->started_at->format('d/m/Y H:i A') : 'N/A',
                'Completed At' => $record->completed_at ? $record->completed_at->format('d/m/Y H:i A') : 'N/A',
                'Status' => $record->completed_at ? 'Completed' : 'In Progress',
                'Duration (minutes)' => $record->getDurationInMinutes() ?? 'N/A',
            ];
        });

        $csvContent = "Student Name,Student Email,Institution,Assessment Title,Attempt Number,Score,Total Marks (Auto-marked),Started At,Completed At,Status,Duration (minutes)\n";
        
        foreach ($data as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}