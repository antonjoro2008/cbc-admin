<?php

namespace App\Filament\Resources\Feedback\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FeedbackMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()
                ->label('Add New'),
            ]);
    }
}
