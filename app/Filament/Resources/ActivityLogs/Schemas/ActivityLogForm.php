<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class ActivityLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('User')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('action')
                            ->label('Action')
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('model_type')
                            ->label('Model Type')
                            ->required(),
                        TextInput::make('model_id')
                            ->label('Model ID')
                            ->numeric()
                            ->required(),
                    ]),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->required(),
                        TextInput::make('ip_address')
                            ->label('IP Address'),
                    ]),
            ]);
    }
}
