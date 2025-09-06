<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\RichTextEntry;

class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('assessment.title')
                                    ->label('Assessment'),
                                TextEntry::make('section.title')
                                    ->label('Section'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('question_number')
                                    ->label('Question Number'),
                                TextEntry::make('marks')
                                    ->label('Marks'),
                                TextEntry::make('question_type')
                                    ->label('Question Type'),
                            ]),
                        TextEntry::make('question_text')
                            ->label('Question Text')
                            ->html(),
                        TextEntry::make('parentQuestion.question_text')
                            ->label('Parent Question')
                            ->placeholder('No parent question')
                            ->html(),
                    ]),
                Section::make('Related Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('answers_count')
                                    ->label('Number of Answers')
                                    ->state(fn($record) => $record->answers()->count()),
                                TextEntry::make('attempt_answers_count')
                                    ->label('Attempt Answers')
                                    ->state(fn($record) => $record->attemptAnswers()->count()),
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