<?php

namespace App\Filament\Resources\TokenTransactions\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TokenTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('User'),
                                TextEntry::make('transaction_type')
                                    ->label('Transaction Type'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('Amount'),
                                TextEntry::make('balance_before')
                                    ->label('Balance Before'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('balance_after')
                                    ->label('Balance After'),
                                TextEntry::make('transaction_date')
                                    ->label('Transaction Date')
                                    ->dateTime(),
                            ]),
                        TextEntry::make('description')
                            ->label('Description'),
                        TextEntry::make('reference')
                            ->label('Reference'),
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
