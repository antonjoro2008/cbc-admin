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
                TextColumn::make('attemptAnswer.question.question_text')
                    ->label('Question')
                    ->html()
                    ->wrap()
                    ->limit(30)
                    ->searchable()
                    ->grow(),
                TextColumn::make('feedback_text')
                    ->label('Feedback Text')
                    ->html()
                    ->wrap()
                    ->limit(30)
                    ->searchable()
                    ->grow(),
                TextColumn::make('attemptAnswer.attempt.user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attemptAnswer.attempt.assessment.title')
                    ->label('Assessment')
                    ->wrap()
                    ->searchable(),
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