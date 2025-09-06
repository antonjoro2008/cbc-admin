<?php

namespace App\Filament\Resources\AttemptAnswers\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AttemptAnswerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attempt Answer Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('question.question_text')
                                    ->label('Question'),
                                TextEntry::make('assessment_attempt.id')
                                    ->label('Assessment Attempt'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('selected_answer')
                                    ->label('Selected Answer'),
                                TextEntry::make('marks_obtained')
                                    ->label('Marks Obtained'),
                            ]),
                        TextEntry::make('explanation')
                            ->label('Explanation'),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('is_correct')
                                    ->label('Correct Answer')
                                    ->boolean(),
                                TextEntry::make('is_active')
                                    ->label('Active')
                                    ->boolean(),
                            ]),
                    ]),
                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
