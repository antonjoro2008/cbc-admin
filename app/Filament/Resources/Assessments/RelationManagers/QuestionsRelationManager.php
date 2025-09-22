<?php

namespace App\Filament\Resources\Assessments\RelationManagers;

use App\Models\AssessmentSection;
use App\Models\Question;
use App\Models\Answer;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\IconEntry;

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
                $query->with(['answers', 'media']);
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
                            ->label('📝 Answers')
                            ->extraAttributes(['class' => 'answers-section'])
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
                                
                                // Nested media repeater for this answer
                                Repeater::make('answer_media')
                                    ->label('Answer Media')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('media_type')
                                                    ->label('Media Type')
                                                    ->options([
                                                        'image' => 'Image',
                                                        'video' => 'Video',
                                                        'audio' => 'Audio',
                                                        'pdf' => 'PDF Document',
                                                        'doc' => 'Word Document',
                                                    ])
                                                    ->placeholder('Select media type...'),
                                                FileUpload::make('file_path')
                                                    ->label('File')
                                                    ->disk('public')
                                                    ->directory('answer-media')
                                                    ->visibility('public')
                                                    ->required(),
                                            ]),
                                        Textarea::make('caption')
                                            ->label('Caption')
                                            ->rows(2)
                                            ->placeholder('Enter caption for this media...')
                                            ->columnSpanFull(),
                                    ])
                                    ->defaultItems(0)
                                    ->minItems(0)
                                    ->maxItems(5)
                                    ->addActionLabel('Add Media')
                                    ->reorderable(true)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['media_type'] ?? 'Media Item')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(2)
                            ->minItems(1)
                            ->maxItems(10)
                            ->addActionLabel('Add Answer')
                            ->reorderable(true)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['answer_text'] ?? 'Answer')
                            ->columnSpanFull(),
                        Repeater::make('media')
                            ->label('🎬 Question Media')
                            ->extraAttributes(['class' => 'media-section'])
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('media_type')
                                            ->label('Media Type')
                                            ->options([
                                                'image' => 'Image',
                                                'video' => 'Video',
                                                'audio' => 'Audio',
                                                'pdf' => 'PDF Document',
                                                'doc' => 'Word Document',
                                            ])
                                            ->placeholder('Select media type...'),
                                        FileUpload::make('file_path')
                                            ->label('File')
                                            ->disk('public')
                                            ->directory('question-media')
                                            ->visibility('public')
                                            ->required(),
                                    ]),
                                Textarea::make('caption')
                                    ->label('Caption')
                                    ->rows(2)
                                    ->placeholder('Enter caption for this media...')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(5)
                            ->addActionLabel('Add Media')
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
                                $answer = $record->answers()->create([
                                    'answer_text' => $answerData['answer_text'],
                                    'is_correct' => $answerData['is_correct'] ?? false,
                                    'explanation' => $answerData['explanation'] ?? null,
                                ]);
                                
                                // Create answer media if provided
                                if (isset($answerData['answer_media']) && is_array($answerData['answer_media'])) {
                                    foreach ($answerData['answer_media'] as $mediaData) {
                                        if (!empty($mediaData['file_path']) && !empty($mediaData['media_type'])) {
                                            $answer->media()->create([
                                                'media_type' => $mediaData['media_type'],
                                                'file_path' => $mediaData['file_path'],
                                                'caption' => $mediaData['caption'] ?? null,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Create media for the question
                        if (isset($data['media']) && is_array($data['media'])) {
                            foreach ($data['media'] as $mediaData) {
                                if (!empty($mediaData['file_path']) && !empty($mediaData['media_type'])) {
                                    $record->media()->create([
                                        'media_type' => $mediaData['media_type'],
                                        'file_path' => $mediaData['file_path'],
                                        'caption' => $mediaData['caption'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->modalHeading('Edit Question')
                        ->modalWidth('4xl')
                        ->fillForm(function ($record): array {
                            // Load the question with its answers, answer media, and question media
                            $question = $record->load(['answers.media', 'media']);
                            
                            // Map the answers to the repeater format including nested answer media
                            $answers = $question->answers->map(function ($answer) {
                                // Map answer media to the nested repeater format
                                $answerMedia = $answer->media->map(function ($mediaItem) {
                                    return [
                                        'media_type' => $mediaItem->media_type,
                                        'file_path' => $mediaItem->file_path,
                                        'caption' => $mediaItem->caption,
                                    ];
                                })->toArray();
                                
                                return [
                                    'answer_text' => $answer->answer_text,
                                    'is_correct' => $answer->is_correct,
                                    'explanation' => $answer->explanation,
                                    'answer_media' => $answerMedia,
                                ];
                            })->toArray();
                            
                            // Map the media to the repeater format
                            $media = $question->media->map(function ($mediaItem) {
                                return [
                                    'media_type' => $mediaItem->media_type,
                                    'file_path' => $mediaItem->file_path,
                                    'caption' => $mediaItem->caption,
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
                                'media' => $media,
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
                                ->label('📝 Answers')
                                ->extraAttributes(['class' => 'answers-section'])
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
                                    
                                    // Nested media repeater for this answer
                                    Repeater::make('answer_media')
                                        ->label('Answer Media')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('media_type')
                                                        ->label('Media Type')
                                                        ->options([
                                                            'image' => 'Image',
                                                            'video' => 'Video',
                                                            'audio' => 'Audio',
                                                            'pdf' => 'PDF Document',
                                                            'doc' => 'Word Document',
                                                        ])
                                                        ->placeholder('Select media type...'),
                                                    FileUpload::make('file_path')
                                                        ->label('File')
                                                        ->disk('public')
                                                        ->directory('answer-media')
                                                        ->visibility('public')
                                                        ->required(),
                                                ]),
                                            Textarea::make('caption')
                                                ->label('Caption')
                                                ->rows(2)
                                                ->placeholder('Enter caption for this media...')
                                                ->columnSpanFull(),
                                        ])
                                        ->defaultItems(0)
                                        ->minItems(0)
                                        ->maxItems(5)
                                        ->addActionLabel('Add Media')
                                        ->reorderable(true)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['media_type'] ?? 'Media Item')
                                        ->columnSpanFull(),
                                ])
                                ->defaultItems(2)
                                ->minItems(1)
                                ->maxItems(10)
                                ->addActionLabel('Add Answer')
                                ->reorderable(true)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['answer_text'] ?? 'Answer')
                                ->columnSpanFull(),
                            Repeater::make('media')
                                ->label('🎬 Question Media')
                                ->extraAttributes(['class' => 'media-section'])
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('media_type')
                                                ->label('Media Type')
                                                ->options([
                                                    'image' => 'Image',
                                                    'video' => 'Video',
                                                    'audio' => 'Audio',
                                                    'pdf' => 'PDF Document',
                                                    'doc' => 'Word Document',
                                                ])
                                                ->placeholder('Select media type...'),
                                            FileUpload::make('file_path')
                                                ->label('File')
                                                ->disk('public')
                                                ->directory('question-media')
                                                ->visibility('public')
                                                ->required(),
                                        ]),
                                    Textarea::make('caption')
                                        ->label('Caption')
                                        ->rows(2)
                                        ->placeholder('Enter caption for this media...')
                                        ->columnSpanFull(),
                                ])
                                ->defaultItems(0)
                                ->minItems(0)
                                ->maxItems(5)
                                ->addActionLabel('Add Media')
                                ->reorderable(true)
                                ->collapsible()
                                ->columnSpanFull(),
                        ])
                        ->after(function (array $data, $record): void {
                            // Update answers for the question
                            if (isset($data['answers']) && is_array($data['answers'])) {
                                // Delete existing answers (this will cascade delete answer media)
                                $record->answers()->delete();
                                
                                // Create new answers
                                foreach ($data['answers'] as $answerData) {
                                    $answer = $record->answers()->create([
                                        'answer_text' => $answerData['answer_text'],
                                        'is_correct' => $answerData['is_correct'] ?? false,
                                        'explanation' => $answerData['explanation'] ?? null,
                                    ]);
                                    
                                    // Create answer media if provided
                                    if (isset($answerData['answer_media']) && is_array($answerData['answer_media'])) {
                                        foreach ($answerData['answer_media'] as $mediaData) {
                                            if (!empty($mediaData['file_path']) && !empty($mediaData['media_type'])) {
                                                $answer->media()->create([
                                                    'media_type' => $mediaData['media_type'],
                                                    'file_path' => $mediaData['file_path'],
                                                    'caption' => $mediaData['caption'] ?? null,
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // Update media for the question
                            if (isset($data['media']) && is_array($data['media'])) {
                                // Delete existing media
                                $record->media()->delete();
                                
                                // Create new media
                                foreach ($data['media'] as $mediaData) {
                                    if (!empty($mediaData['file_path']) && !empty($mediaData['media_type'])) {
                                        $record->media()->create([
                                            'media_type' => $mediaData['media_type'],
                                            'file_path' => $mediaData['file_path'],
                                            'caption' => $mediaData['caption'] ?? null,
                                        ]);
                                    }
                                }
                            }
                        }),
                    
                    // Media Action - Shows question media in slideOver
                    Action::make('view_media')
                        ->label('🎬 Media')
                        ->extraAttributes(['class' => 'media-action'])
                        ->modalHeading('Question Media')
                        ->modalWidth('4xl')
                        ->infolist([
                            TextEntry::make('question_text')
                                ->label('Question')
                                ->html()
                                ->columnSpanFull(),
                            RepeatableEntry::make('media')
                                ->label('Media Files')
                                ->schema([
                                    TextEntry::make('media_type')
                                        ->label('Type')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'image' => 'success',
                                            'video' => 'info',
                                            'audio' => 'warning',
                                            'pdf' => 'danger',
                                            'doc' => 'primary',
                                            default => 'gray',
                                        }),
                                    TextEntry::make('file_path')
                                        ->label('File')
                                        ->url(fn ($record): string => asset('storage/' . $record->file_path))
                                        ->openUrlInNewTab(),
                                    TextEntry::make('caption')
                                        ->label('Caption')
                                        ->placeholder('No caption provided'),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ]),
                    
                    // Answers Action - Shows question answers in slideOver
                    Action::make('view_answers')
                        ->label('📝 Answers')
                        ->extraAttributes(['class' => 'answers-action'])
                        ->modalHeading('Question Answers')
                        ->modalWidth('4xl')
                        ->infolist([
                            TextEntry::make('question_text')
                                ->label('Question')
                                ->html()
                                ->columnSpanFull(),
                            RepeatableEntry::make('answers')
                                ->label('📝 Answers')
                                ->extraAttributes(['class' => 'answers-entry'])
                                ->schema([
                                    TextEntry::make('answer_text')
                                        ->label('Answer')
                                        ->html(),
                                    IconEntry::make('is_correct')
                                        ->label('Correct')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-check-circle')
                                        ->falseIcon('heroicon-o-x-circle')
                                        ->trueColor('success')
                                        ->falseColor('danger'),
                                    TextEntry::make('explanation')
                                        ->label('Explanation')
                                        ->html()
                                        ->placeholder('No explanation provided'),
                                    
                                    // Show answer media if any
                                    RepeatableEntry::make('media')
                                        ->label('Answer Media')
                                        ->schema([
                                            TextEntry::make('media_type')
                                                ->label('Type')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'image' => 'success',
                                                    'video' => 'info',
                                                    'audio' => 'warning',
                                                    'pdf' => 'danger',
                                                    'doc' => 'primary',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('file_path')
                                                ->label('File')
                                                ->url(fn ($record): string => asset('storage/' . $record->file_path))
                                                ->openUrlInNewTab(),
                                            TextEntry::make('caption')
                                                ->label('Caption')
                                                ->placeholder('No caption provided'),
                                        ])
                                        ->columns(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                    
                    DeleteAction::make(),
                ])
            ])
            ->defaultSort('question_number', 'asc')
            ->modifyQueryUsing(function ($query) {
                $query->orderByRaw('CAST(question_number AS UNSIGNED)');
            });
    }
}
