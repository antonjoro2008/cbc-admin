<?php

namespace App\Filament\Resources\Answers\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AnswerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Answer Information')
                    ->schema([
                        TextEntry::make('question.question_text')
                            ->label('Question'),
                        TextEntry::make('answer_text')
                            ->label('Answer Text')
                            ->html(),
                        TextEntry::make('explanation')
                            ->label('Explanation')
                            ->html(),
                        TextEntry::make('is_correct')
                            ->label('Correct Answer'),
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