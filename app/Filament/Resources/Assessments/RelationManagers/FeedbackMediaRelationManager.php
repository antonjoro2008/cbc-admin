<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class FeedbackMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'feedbackMedia';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('feedback.subject')
                    ->label('Feedback Subject')
                    ->searchable()
                    ->limit(50),
                BadgeColumn::make('media_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'image',
                        'success' => 'video',
                        'warning' => 'audio',
                        'info' => 'pdf',
                        'secondary' => 'doc',
                        'danger' => 'link',
                    ]),
                ImageColumn::make('media_url')
                    ->label('Media URL')
                    ->circular()
                    ->size(40),
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
