<?php

namespace App\Filament\Resources\Answers\Schemas;

use App\Models\Question;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('question_id')
                    ->label('Question')
                    ->options(Question::all()->mapWithKeys(fn($q) => [$q->id => strip_tags($q->question_text)]))
                    ->columnSpanFull()
                    ->searchable()
                    ->required(),
                Textarea::make('answer_text')
                    ->label('Answer Text')
                    ->rows(5)
                    ->columnSpanFull()
                    ->required(),
                RichEditor::make('explanation')
                    ->label('Explanation')
                    ->columnSpanFull(),
                Toggle::make('is_correct')
                    ->label('Correct Answer')
                    ->columnSpanFull()
                    ->default(false),
            ]);
    }
}