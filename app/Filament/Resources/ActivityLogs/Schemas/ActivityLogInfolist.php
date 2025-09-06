<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('User'),
                                TextEntry::make('action')
                                    ->label('Action'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('model_type')
                                    ->label('Model Type'),
                                TextEntry::make('model_id')
                                    ->label('Model ID'),
                            ]),
                        TextEntry::make('description')
                            ->label('Description'),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                TextEntry::make('ip_address')
                                    ->label('IP Address'),
                            ]),
                    ]),
            ]);
    }
}
