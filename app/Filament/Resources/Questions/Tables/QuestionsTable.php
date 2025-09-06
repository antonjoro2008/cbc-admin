<?php

namespace App\Filament\Resources\Questions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question_number')
                    ->label('No.')
                    ->sortable(),
                TextColumn::make('assessment.title')
                    ->label('Assessment')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('section.title')
                    ->label('Section')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('question_text')
                    ->label('Question')
                    ->html()
                    ->searchable()
                    ->limit(80),
                BadgeColumn::make('question_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'multiple_choice',
                        'success' => 'essay',
                        'warning' => 'short_answer',
                        'info' => 'true_false',
                        'danger' => 'matching',
                        'secondary' => 'fill_blank',
                    ]),
                TextColumn::make('marks')
                    ->label('Marks')
                    ->sortable(),
                TextColumn::make('parentQuestion.question_text')
                    ->label('Parent Question')
                    ->limit(50)
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