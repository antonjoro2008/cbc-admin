<?php

namespace App\Filament\Resources\FeedbackMedia\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class FeedbackMediaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('feedback.subject')
                                    ->label('Feedback Subject')
                                    ->limit(100),
                                TextEntry::make('media_type')
                                    ->label('Media Type'),
                            ]),
                        TextEntry::make('media_url')
                            ->label('Media URL'),
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
