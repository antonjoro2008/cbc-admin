<?php

namespace App\Filament\Resources\QuestionMedia\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class QuestionMediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('question.question_text')
                //     ->label('Question')
                //     ->searchable()
                //     ->html()
                //     ->wrap()
                //     ->limit(50),
                BadgeColumn::make('media_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'image',
                        'success' => 'video',
                        'warning' => 'audio',
                        'info' => 'pdf',
                        'secondary' => 'doc',
                    ]),
                ImageColumn::make('file_path')
                    ->label('Resource')
                    ->circular()
                    ->size(40),
                TextColumn::make('caption')
                    ->label('Caption')
                    ->searchable()
                    ->html()
                    ->wrap()
                    ->limit(50),
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