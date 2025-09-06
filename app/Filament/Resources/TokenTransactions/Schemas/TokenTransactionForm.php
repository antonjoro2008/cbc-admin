<?php

namespace App\Filament\Resources\TokenTransactions\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class TokenTransactionForm
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
                        TextInput::make('transaction_type')
                            ->label('Transaction Type')
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required(),
                        TextInput::make('balance_before')
                            ->label('Balance Before')
                            ->numeric()
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('balance_after')
                            ->label('Balance After')
                            ->numeric()
                            ->required(),
                        DateTimePicker::make('transaction_date')
                            ->label('Transaction Date')
                            ->required(),
                    ]),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                TextInput::make('reference')
                    ->label('Reference'),
            ]);
    }
}
