<?php

namespace App\Filament\Resources\Questions\RelationManagers;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ChildQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'childQuestions';

    protected static ?string $relatedResource = QuestionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
