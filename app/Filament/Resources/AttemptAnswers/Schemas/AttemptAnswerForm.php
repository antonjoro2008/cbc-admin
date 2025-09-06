<?php

namespace App\Filament\Resources\AttemptAnswers\Schemas;

use App\Models\Question;
use App\Models\AssessmentAttempt;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AttemptAnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('question_id')
                            ->label('Question')
                            ->options(Question::all()->pluck('question_text', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('assessment_attempt_id')
                            ->label('Assessment Attempt')
                            ->options(AssessmentAttempt::all()->pluck('id', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('selected_answer')
                            ->label('Selected Answer')
                            ->required(),
                        TextInput::make('marks_obtained')
                            ->label('Marks Obtained')
                            ->numeric()
                            ->minValue(0),
                    ]),
                Textarea::make('explanation')
                    ->label('Explanation')
                    ->rows(3),
                Grid::make(2)
                    ->schema([
                        Toggle::make('is_correct')
                            ->label('Correct Answer')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
