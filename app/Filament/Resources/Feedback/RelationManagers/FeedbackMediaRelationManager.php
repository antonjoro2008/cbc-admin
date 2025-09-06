<?php

namespace App\Filament\Resources\Feedback\RelationManagers;

use App\Filament\Resources\FeedbackMedia\FeedbackMediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FeedbackMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $relatedResource = FeedbackMediaResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()
                ->label('Add New'),
            ]);
    }
}
