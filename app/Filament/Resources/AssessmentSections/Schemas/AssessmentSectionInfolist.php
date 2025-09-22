<?php

namespace App\Filament\Resources\AssessmentSections\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AssessmentSectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section Information')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Section Title')
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('section_order')
                                    ->label('Section Order'),
                                TextEntry::make('total_marks')
                                    ->label('Total Marks'),
                            ]),
                        TextEntry::make('description')
                            ->label('Description'),
                    ]),
                Section::make('Related Information')
                    ->schema([
                        TextEntry::make('questions_count')
                            ->label('Number of Questions')
                            ->state(fn($record) => $record->questions()->count())
                            ->columnSpanFull(),
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
