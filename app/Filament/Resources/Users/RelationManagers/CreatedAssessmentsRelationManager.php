<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Assessments\AssessmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class CreatedAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'createdAssessments';

    protected static ?string $relatedResource = AssessmentResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
