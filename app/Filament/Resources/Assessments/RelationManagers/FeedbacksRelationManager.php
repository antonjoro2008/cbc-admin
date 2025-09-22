<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class FeedbacksRelationManager extends RelationManager
{
    protected static string $relationship = 'feedbacks';

    public function table(Table $table): Table
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
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
