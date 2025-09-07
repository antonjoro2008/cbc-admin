<?php

namespace App\Filament\Resources\Feedbacks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FeedbacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attempt_answer_id')
                    ->label('Attempt Answer ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('attemptAnswer.question.question_text')
                    ->label('Question')
                    ->html()
                    ->wrap()
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('feedback_text')
                    ->label('Feedback Text')
                    ->html()
                    ->wrap()
                    ->limit(50)
                    ->searchable(),
                ToggleColumn::make('ai_generated')
                    ->label('AI Generated'),
                TextColumn::make('attemptAnswer.attempt.user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attemptAnswer.attempt.assessment.title')
                    ->label('Assessment')
                    ->searchable(),
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