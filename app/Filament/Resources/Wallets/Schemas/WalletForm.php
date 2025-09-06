<?php

namespace App\Filament\Resources\Wallets\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WalletForm
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
                        TextInput::make('balance')
                            ->label('Balance')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('currency')
                            ->label('Currency')
                            ->default('KES')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
