<?php

namespace App\Filament\Resources\AnswerMedia\Schemas;

use App\Models\Answer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class AnswerMediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('answer_id')
                            ->label('Answer')
                            ->options(Answer::all()->pluck('answer_text', 'id'))
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
                    ->disk('public')
                    ->required(),
                Textarea::make('caption')
                    ->columnSpanFull()
                    ->label('Caption')
                    ->rows(3),
            ]);
    }
}