<?php

namespace App\Filament\Resources\Questions\RelationManagers;

use App\Filament\Resources\AttemptAnswers\AttemptAnswerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AttemptAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'attemptAnswers';

    protected static ?string $relatedResource = AttemptAnswerResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
