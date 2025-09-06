<?php

namespace App\Filament\Resources\TokenUsages\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TokenUsageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Token Usage Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('attempt.id')
                                    ->label('Assessment Attempt ID'),
                                TextEntry::make('tokens_used')
                                    ->label('Tokens Used'),
                            ]),
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
