<?php

namespace App\Filament\Resources\Feedbacks\Schemas;

use App\Models\AttemptAnswer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class FeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('attempt_answer_id')
                            ->label('Attempt Answer')
                            ->options(AttemptAnswer::all()->pluck('id', 'id'))
                            ->searchable()
                            ->required()
                            ->relationship('attemptAnswer', 'id'),
                        Toggle::make('ai_generated')
                            ->label('AI Generated')
                            ->default(false),
                    ]),
                RichEditor::make('feedback_text')
                    ->label('Feedback Text')
                    ->required()
                    ->placeholder('Enter feedback for the student\'s answer...'),
            ]);
    }
}
