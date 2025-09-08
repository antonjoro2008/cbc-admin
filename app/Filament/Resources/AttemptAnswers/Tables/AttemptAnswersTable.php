<?php

namespace App\Filament\Resources\AttemptAnswers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AttemptAnswersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('question.question_text')
                //     ->label('Question')
                //     ->html()
                //     ->wrap()
                //     ->searchable()
                //     ->limit(40),
                TextColumn::make('assessment_attempt.id')
                    ->label('Attempt ID')
                    ->sortable(),
                TextColumn::make('selected_answer')
                    ->label('Selected Answer')
                    ->searchable()
                    ->limit(100),
                TextColumn::make('marks_obtained')
                    ->label('Marks')
                    ->sortable(),
                TextColumn::make('explanation')
                    ->label('Explanation')
                    ->searchable()
                    ->limit(100),
                ToggleColumn::make('is_correct')
                    ->label('Correct'),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}