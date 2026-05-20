<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('email')
                                    ->label('Email'),
                            ]),
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('phone_number')
                                    ->label('Phone'),
                                TextEntry::make('user_type')
                                    ->label('User type')
                                    ->badge(),
                                TextEntry::make('admission_number')
                                    ->label('Admission #')
                                    ->placeholder('—'),
                            ]),
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('institution.name')
                                    ->label('Institution')
                                    ->placeholder('—'),
                                TextEntry::make('grade_level')
                                    ->label('Grade')
                                    ->placeholder('—'),
                            ]),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean()
                            ->columnSpanFull(),
                    ]),
                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
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
