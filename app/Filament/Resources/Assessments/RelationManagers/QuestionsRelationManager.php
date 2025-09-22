<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use App\Models\AssessmentSection;
use App\Models\Question;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Grid;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question_number')
                    ->label('Question #')
                    ->sortable(),
                TextColumn::make('question_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mcq' => 'info',
                        'essay' => 'warning',
                        'short_answer' => 'success',
                        'true_false' => 'primary',
                        'matching' => 'secondary',
                        'fill_blank' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('question_text')
                    ->label('Question')
                    ->limit(50)
                    ->html(),
                TextColumn::make('section.title')
                    ->label('Section')
                    ->sortable(),
                TextColumn::make('marks')
                    ->label('Marks')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Question')
                    ->modalHeading('Add New Question')
                    ->modalWidth('4xl')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('section_id')
                                    ->label('Section')
                                    ->options(AssessmentSection::all()->pluck('title', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live(),
                                TextInput::make('question_number')
                                    ->label('Question Number')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('marks')
                                    ->label('Marks')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                Select::make('question_type')
                                    ->label('Question Type')
                                    ->options([
                                        'mcq' => 'Multiple Choice',
                                        'essay' => 'Essay',
                                        'short_answer' => 'Short Answer',
                                        'true_false' => 'True/False',
                                        'matching' => 'Matching',
                                        'fill_blank' => 'Fill in the Blank',
                                    ])
                                    ->required()
                                    ->live(),
                                Select::make('parent_question_id')
                                    ->label('Parent Question (Optional)')
                                    ->options(function (RelationManager $livewire) {
                                        return Question::where('assessment_id', $livewire->ownerRecord->id)
                                            ->pluck('question_text', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('Select parent question if this is a sub-question'),
                            ]),
                        RichEditor::make('question_text')
                            ->label('Question Text')
                            ->required()
                            ->placeholder('Enter the question text...')
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        // Automatically set the assessment_id
                        $data['assessment_id'] = $livewire->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Question')
                    ->modalWidth('4xl')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Select::make('section_id')
                                    ->label('Section')
                                    ->options(AssessmentSection::all()->pluck('title', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live(),
                                TextInput::make('question_number')
                                    ->label('Question Number')
                                    ->numeric()
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('marks')
                                    ->label('Marks')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                Select::make('question_type')
                                    ->label('Question Type')
                                    ->options([
                                        'mcq' => 'Multiple Choice',
                                        'essay' => 'Essay',
                                        'short_answer' => 'Short Answer',
                                        'true_false' => 'True/False',
                                        'matching' => 'Matching',
                                        'fill_blank' => 'Fill in the Blank',
                                    ])
                                    ->required()
                                    ->live(),
                                Select::make('parent_question_id')
                                    ->label('Parent Question (Optional)')
                                    ->options(function (RelationManager $livewire) {
                                        return Question::where('assessment_id', $livewire->ownerRecord->id)
                                            ->pluck('question_text', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('Select parent question if this is a sub-question'),
                            ]),
                        RichEditor::make('question_text')
                            ->label('Question Text')
                            ->required()
                            ->placeholder('Enter the question text...')
                            ->columnSpanFull(),
                    ]),
                DeleteAction::make(),
            ])
            ->defaultSort('question_number');
    }
}
