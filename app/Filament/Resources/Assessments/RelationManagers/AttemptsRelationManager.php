<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('attempt_number')
                    ->label('Attempt #')
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Score')
                    ->sortable(),
                TextColumn::make('total_marks')
                    ->label('Total Marks (Auto)')
                    ->getStateUsing(function ($record) {
                        // Get total marks for auto-marked questions only
                        $totalMarks = $record->assessment->questions()
                            ->whereIn('question_type', ['mcq', 'true_false', 'matching', 'fill_blank'])
                            ->sum('marks');

                        return $totalMarks ?: 'N/A';
                    })
                    ->sortable(false), // Can't sort computed columns
            ])
            ->filters([
                SelectFilter::make('attempt_number')
                    ->label('Attempt Number')
                    ->options([
                        1 => 'Attempt 1',
                        2 => 'Attempt 2',
                        3 => 'Attempt 3',
                    ])
                    ->multiple(),

                SelectFilter::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('completed')
                    ->label('Completed Attempts')
                    ->query(fn($query) => $query->whereNotNull('completed_at')),

                Filter::make('in_progress')
                    ->label('In Progress Attempts')
                    ->query(fn($query) => $query->whereNull('completed_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('answers')
                    ->label('Answers')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->slideOver()
                    ->modalHeading('Attempt Answers')
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->infolist([
                        Section::make('Attempt Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('student.name')
                                            ->label('Student'),
                                        TextEntry::make('attempt_number')
                                            ->label('Attempt Number'),
                                        TextEntry::make('score')
                                            ->label('Score'),
                                        TextEntry::make('started_at')
                                            ->label('Started At')
                                            ->dateTime('d/m/Y H:i A'),
                                        TextEntry::make('completed_at')
                                            ->label('Completed At')
                                            ->dateTime('d/m/Y H:i A'),
                                    ]),
                            ]),
                        RepeatableEntry::make('attemptAnswers')
                            ->label('Answers')
                            ->schema([
                                TextEntry::make('question.question_text')
                                    ->label('Question')
                                    ->html(),
                                TextEntry::make('student_answer_text')
                                    ->label('Student Answer'),
                                TextEntry::make('marks_awarded')
                                    ->label('Marks Awarded'),
                                IconEntry::make('is_correct')
                                    ->label('Correct')
                                    ->boolean(),
                                RepeatableEntry::make('feedback')
                                    ->label('Feedback')
                                    ->schema([
                                        TextEntry::make('feedback_text')
                                            ->label('Feedback Text')
                                            ->html(),
                                        IconEntry::make('ai_generated')
                                            ->label('AI Generated')
                                            ->boolean(),
                                        RepeatableEntry::make('media')
                                            ->label('Feedback Media')
                                            ->schema([
                                                TextEntry::make('media_type')
                                                    ->label('Media Type'),
                                                TextEntry::make('media_url')
                                                    ->label('Media URL'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->fillForm(function ($record): array {
                        // Load the attempt with its answers, questions, feedback, and feedback media
                        $attempt = $record->load(['attemptAnswers.question', 'attemptAnswers.feedback.media', 'student']);
                        
                        return [
                            'student' => $attempt->student,
                            'attempt_number' => $attempt->attempt_number,
                            'score' => $attempt->score,
                            'started_at' => $attempt->started_at,
                            'completed_at' => $attempt->completed_at,
                            'attemptAnswers' => $attempt->attemptAnswers->map(function ($answer) {
                                return [
                                    'question' => $answer->question,
                                    'student_answer_text' => $answer->student_answer_text,
                                    'marks_awarded' => $answer->marks_awarded,
                                    'is_correct' => $answer->is_correct,
                                    'feedback' => $answer->feedback->map(function ($feedback) {
                                        return [
                                            'feedback_text' => $feedback->feedback_text,
                                            'ai_generated' => $feedback->ai_generated,
                                            'media' => $feedback->media->map(function ($media) {
                                                return [
                                                    'media_type' => $media->media_type,
                                                    'media_url' => $media->media_url,
                                                ];
                                            })->toArray(),
                                        ];
                                    })->toArray(),
                                ];
                            })->toArray(),
                        ];
                    }),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
