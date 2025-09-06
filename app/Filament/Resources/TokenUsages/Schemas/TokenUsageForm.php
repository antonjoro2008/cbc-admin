<?php

namespace App\Filament\Resources\TokenUsages\Schemas;

use App\Models\AssessmentAttempt;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TokenUsageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('attempt_id')
                            ->label('Assessment Attempt')
                            ->options(AssessmentAttempt::all()->pluck('id', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('tokens_used')
                            ->label('Tokens Used')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
            ]);
    }
}
