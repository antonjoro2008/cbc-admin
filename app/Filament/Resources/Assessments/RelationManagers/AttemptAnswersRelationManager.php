<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;

class AttemptAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'attemptAnswers';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.question_text')
                    ->label('Question')
                    ->html()
                    ->wrap()
                    ->searchable()
                    ->limit(40),
                TextColumn::make('selected_answer')
                    ->label('Selected Answer')
                    ->searchable()
                    ->limit(100),
                TextColumn::make('marks_obtained')
                    ->label('Marks')
                    ->sortable(),
                TextColumn::make('explanation')
                    ->label('Explanation')
                    ->searchable()
                    ->limit(100),
                ToggleColumn::make('is_correct')
                    ->label('Correct'),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('feedback')
                    ->label('Feedback')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->slideOver()
                    ->modalHeading('Feedback for Attempt Answer')
                    ->modalWidth('4xl')
                    ->infolist([
                        TextEntry::make('question.question_text')
                            ->label('Question')
                            ->html(),
                        TextEntry::make('selected_answer')
                            ->label('Selected Answer'),
                        TextEntry::make('marks_obtained')
                            ->label('Marks Obtained'),
                        TextEntry::make('explanation')
                            ->label('Explanation'),
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
                    ])
                    ->fillForm(function ($record): array {
                        // Load the attempt answer with its feedback and feedback media
                        $attemptAnswer = $record->load(['feedback.media', 'question']);
                        
                        return [
                            'question' => $attemptAnswer->question,
                            'selected_answer' => $attemptAnswer->selected_answer,
                            'marks_obtained' => $attemptAnswer->marks_obtained,
                            'explanation' => $attemptAnswer->explanation,
                            'is_correct' => $attemptAnswer->is_correct,
                            'feedback' => $attemptAnswer->feedback->map(function ($feedback) {
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
                    }),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
