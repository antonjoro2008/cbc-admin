<?php

namespace App\Filament\Resources\AssessmentAttempts\RelationManagers;

use App\Filament\Resources\TokenUsages\TokenUsageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TokenUsageRelationManager extends RelationManager
{
    protected static string $relationship = 'tokenUsage';

    protected static ?string $relatedResource = TokenUsageResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
