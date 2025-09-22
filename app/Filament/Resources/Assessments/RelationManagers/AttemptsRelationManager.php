<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attempt_number')
                    ->label('Attempt #')
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Score')
                    ->sortable(),
                TextColumn::make('total_marks')
                    ->label('Total Marks (Auto)')
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

                SelectFilter::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('completed')
                    ->label('Completed Attempts')
                    ->query(fn($query) => $query->whereNotNull('completed_at')),

                Filter::make('in_progress')
                    ->label('In Progress Attempts')
                    ->query(fn($query) => $query->whereNull('completed_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
