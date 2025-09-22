<?php

namespace App\Filament\Resources\AssessmentSections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AssessmentSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Section Title')
                    ->required()
                    ->placeholder('Enter section title...')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->placeholder('Enter section description...')
                    ->columnSpanFull(),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('section_order')
                            ->label('Section Order')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->placeholder('1, 2, 3...'),
                        TextInput::make('total_marks')
                            ->label('Total Marks')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->placeholder('Total marks for this section'),
                    ]),
            ]);
    }
}
