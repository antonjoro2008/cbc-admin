<?php

namespace App\Filament\Resources\AssessmentAttempts\Schemas;

use App\Models\Assessment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class AssessmentAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('assessment_id')
                            ->label('Assessment')
                            ->options(Assessment::all()->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('user_id')
                            ->label('User')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->required(),
                        DateTimePicker::make('completed_at')
                            ->label('Completed At'),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('total_marks')
                            ->label('Total Marks')
                            ->numeric()
                            ->minValue(0),
                    ]),
                Grid::make(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'abandoned' => 'Abandoned',
                                'timeout' => 'Timeout',
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
