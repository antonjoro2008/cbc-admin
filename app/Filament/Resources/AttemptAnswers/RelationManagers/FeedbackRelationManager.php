<?php

namespace App\Filament\Resources\AttemptAnswers\RelationManagers;

use App\Filament\Resources\Feedbacks\FeedbackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class FeedbackRelationManager extends RelationManager
{
    protected static string $relationship = 'feedback';

    protected static ?string $relatedResource = FeedbackResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
