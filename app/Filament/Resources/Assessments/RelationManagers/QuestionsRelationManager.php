<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use App\Models\AssessmentSection;
use App\Models\Question;
use App\Models\Answer;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->with('answers');
            })
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
                    ->wrap()
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
                        Repeater::make('answers')
                            ->label('Answers')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Textarea::make('answer_text')
                                            ->label('Answer Text')
                                            ->rows(3)
                                            ->required()
                                            ->placeholder('Enter answer text...'),
                                        Toggle::make('is_correct')
                                            ->label('Correct Answer')
                                            ->default(false),
                                    ]),
                                RichEditor::make('explanation')
                                    ->label('Explanation')
                                    ->placeholder('Enter explanation for this answer...')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(2)
                            ->minItems(1)
                            ->maxItems(10)
                            ->addActionLabel('Add Answer')
                            ->reorderable(true)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        // Automatically set the assessment_id
                        $data['assessment_id'] = $livewire->ownerRecord->id;
                        return $data;
                    })
                    ->after(function (array $data, $record): void {
                        // Create answers for the question
                        if (isset($data['answers']) && is_array($data['answers'])) {
                            foreach ($data['answers'] as $answerData) {
                                $record->answers()->create([
                                    'answer_text' => $answerData['answer_text'],
                                    'is_correct' => $answerData['is_correct'] ?? false,
                                    'explanation' => $answerData['explanation'] ?? null,
                                ]);
                            }
                        }
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Question')
                    ->modalWidth('4xl')
                    ->fillForm(function ($record): array {
                        // Load the question with its answers
                        $question = $record->load('answers');
                        
                        // Map the answers to the repeater format
                        $answers = $question->answers->map(function ($answer) {
                            return [
                                'answer_text' => $answer->answer_text,
                                'is_correct' => $answer->is_correct,
                                'explanation' => $answer->explanation,
                            ];
                        })->toArray();
                        
                        return [
                            'section_id' => $question->section_id,
                            'question_number' => $question->question_number,
                            'marks' => $question->marks,
                            'question_type' => $question->question_type,
                            'parent_question_id' => $question->parent_question_id,
                            'question_text' => $question->question_text,
                            'answers' => $answers,
                        ];
                    })
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
                        Repeater::make('answers')
                            ->label('Answers')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Textarea::make('answer_text')
                                            ->label('Answer Text')
                                            ->rows(3)
                                            ->required()
                                            ->placeholder('Enter answer text...'),
                                        Toggle::make('is_correct')
                                            ->label('Correct Answer')
                                            ->default(false),
                                    ]),
                                RichEditor::make('explanation')
                                    ->label('Explanation')
                                    ->placeholder('Enter explanation for this answer...')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(2)
                            ->minItems(1)
                            ->maxItems(10)
                            ->addActionLabel('Add Answer')
                            ->reorderable(true)
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->after(function (array $data, $record): void {
                        // Update answers for the question
                        if (isset($data['answers']) && is_array($data['answers'])) {
                            // Delete existing answers
                            $record->answers()->delete();
                            
                            // Create new answers
                            foreach ($data['answers'] as $answerData) {
                                $record->answers()->create([
                                    'answer_text' => $answerData['answer_text'],
                                    'is_correct' => $answerData['is_correct'] ?? false,
                                    'explanation' => $answerData['explanation'] ?? null,
                                ]);
                            }
                        }
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('question_number', 'asc')
            ->modifyQueryUsing(function ($query) {
                $query->orderByRaw('CAST(question_number AS UNSIGNED)');
            });
    }
}
