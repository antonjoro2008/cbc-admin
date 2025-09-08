<?php

namespace App\Filament\Resources\AssessmentAttempts\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AssessmentAttemptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Attempt Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('assessment.title')
                                    ->label('Assessment'),
                                TextEntry::make('user.name')
                                    ->label('User'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('started_at')
                                    ->label('Started At')
                                    ->dateTime(),
                                TextEntry::make('completed_at')
                                    ->label('Completed At')
                                    ->dateTime(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('score')
                                    ->label('Score'),
                                TextEntry::make('total_marks')
                                    ->label('Total Marks'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status'),
                                TextEntry::make('is_active')
                                    ->label('Active'),
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