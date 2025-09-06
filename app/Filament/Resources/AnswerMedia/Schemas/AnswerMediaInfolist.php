<?php

namespace App\Filament\Resources\AnswerMedia\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Schema;

class AnswerMediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('answer.answer_text')
                                    ->label('Answer')
                                    ->limit(100),
                                TextEntry::make('media_type')
                                    ->label('Media Type'),
                            ]),
                        TextEntry::make('file_path')
                            ->label('File Path'),
                        TextEntry::make('caption')
                            ->label('Caption'),
                    ]),
                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
