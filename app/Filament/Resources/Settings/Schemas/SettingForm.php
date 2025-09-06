<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('key_name')
                            ->label('Setting Key')
                            ->required()
                            ->placeholder('e.g., site_name, maintenance_mode, max_upload_size')
                            ->helperText('Unique identifier for this setting'),
                            TextInput::make('value')
                            ->label('Setting Value')
                            ->required()
                            ->placeholder('Enter the value for this setting')
                            ->helperText('The actual value stored for this setting key'),
                    ]),
            ]);
    }
}
