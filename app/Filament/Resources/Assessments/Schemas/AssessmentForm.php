<?php

namespace App\Filament\Resources\Assessments\Schemas;

use App\Models\Subject;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('subject_id')
                            ->label('Subject')
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('title')
                            ->label('Title')
                            ->required(),
                    ]),
                RichEditor::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Grid::make(3)
                    ->schema([
                        TextInput::make('grade_level')
                            ->label('Grade Level'),
                        TextInput::make('paper_code')
                            ->label('Paper Code'),
                        TextInput::make('paper_number')
                            ->label('Paper Number'),
                    ]),
                Grid::make(3)
                    ->schema([
                        TextInput::make('year')
                            ->label('Year')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2030),
                        TextInput::make('exam_body')
                            ->label('Exam Body'),
                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(1),
                    ]),
                RichEditor::make('instructions')
                    ->label('Instructions')
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        Toggle::make('uses_answer_sheet')
                            ->label('Uses Answer Sheet')
                            ->default(false),
                    ]),
            ]);
    }
}
