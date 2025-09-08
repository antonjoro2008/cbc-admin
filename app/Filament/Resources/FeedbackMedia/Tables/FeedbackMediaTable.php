<?php

namespace App\Filament\Resources\FeedbackMedia\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class FeedbackMediaTable
{
    public static function configure(Table $table): Table
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
            ]);
    }
}