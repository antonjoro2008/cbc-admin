<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Assessment;
use App\Models\AssessmentSection;
use App\Models\Question;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('assessment_id')
                            ->label('Assessment')
                            ->options(Assessment::all()->pluck('title', 'id'))
                            ->searchable()
                            ->required()
                            ->relationship('assessment', 'title'),
                        Select::make('section_id')
                            ->label('Section')
                            ->options(AssessmentSection::all()->pluck('title', 'id'))
                            ->searchable()
                            ->required()
                            ->relationship('section', 'title'),
                    ]),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('question_number')
                            ->label('Question Number')
                            ->numeric()
                            ->required(),
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
                            ->required(),
                    ]),
                RichEditor::make('question_text')
                    ->label('Question Text')
                    ->columnSpanFull()
                    ->required()
                    ->placeholder('Enter the question text...'),
                Select::make('parent_question_id')
                    ->label('Parent Question (Optional)')
                    ->columnSpanFull()
                    ->options(Question::all()->pluck('question_text', 'id'))
                    ->searchable()
                    ->placeholder('Select parent question if this is a sub-question')
                    ->relationship('parentQuestion', 'question_text'),
            ]);
    }
}