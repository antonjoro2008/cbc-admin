<?php

namespace App\Filament\Resources\Feedbacks\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Schema;

class FeedbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Feedback Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('attempt_answer_id')
                                    ->label('Attempt Answer ID'),
                                IconEntry::make('ai_generated')
                                    ->label('AI Generated')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-cpu-chip')
                                    ->falseIcon('heroicon-o-user'),
                            ]),
                        TextEntry::make('feedback_text')
                            ->label('Feedback Text')
                            ->markdown(),
                    ]),
                Section::make('Related Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('attemptAnswer.question.question_text')
                                    ->label('Question')
                                    ->limit(100),
                                TextEntry::make('attemptAnswer.student_answer_text')
                                    ->label('Student Answer')
                                    ->limit(100),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('attemptAnswer.attempt.user.name')
                                    ->label('Student'),
                                TextEntry::make('attemptAnswer.attempt.assessment.title')
                                    ->label('Assessment'),
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
