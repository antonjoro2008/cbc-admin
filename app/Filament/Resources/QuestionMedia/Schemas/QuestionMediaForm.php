<?php

namespace App\Filament\Resources\QuestionMedia\Schemas;

use App\Models\Question;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class QuestionMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('question_id')
                            ->label('Question')
                            ->options(Question::all()->mapWithKeys(fn($q) => [$q->id => strip_tags($q->question_text)]))
                            ->searchable()
                            ->required(),
                        Select::make('media_type')
                            ->label('Media Type')
                            ->options([
                                'image' => 'Image',
                                'video' => 'Video',
                                'audio' => 'Audio',
                                'pdf' => 'PDF',
                                'doc' => 'Document',
                            ])
                            ->required(),
                    ]),
                FileUpload::make('file_path')
                    ->label('File')
                    ->columnSpanFull()
                    ->required(),
                RichEditor::make('caption')
                    ->label('Caption')
                    ->columnSpanFull(),
            ]);
    }
}