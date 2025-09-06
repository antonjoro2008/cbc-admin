<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use App\Filament\Resources\AssessmentAttempts\AssessmentAttemptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $relatedResource = AssessmentAttemptResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
