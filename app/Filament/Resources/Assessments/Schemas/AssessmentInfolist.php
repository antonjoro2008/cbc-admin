<?php

namespace App\Filament\Resources\Assessments\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class AssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assessment Details')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title'),
                                TextEntry::make('subject.name')
                                    ->label('Subject'),
                            ]),
                        TextEntry::make('description')
                            ->label('Description')
                            ->html(),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('grade_level')
                                    ->label('Grade Level'),
                                TextEntry::make('paper_code')
                                    ->label('Paper Code'),
                                TextEntry::make('paper_number')
                                    ->label('Paper Number'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('year')
                                    ->label('Year'),
                                TextEntry::make('exam_body')
                                    ->label('Exam Body'),
                                TextEntry::make('duration_minutes')
                                    ->label('Duration (minutes)'),
                            ]),
                        TextEntry::make('instructions')
                            ->label('Instructions')
                            ->html(),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('creator.name')
                                    ->label('Created By'),
                                TextEntry::make('uses_answer_sheet')
                                    ->label('Uses Answer Sheet'),
                            ]),
                    ]),
            ]);
    }
}