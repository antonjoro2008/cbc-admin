<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('User'),
                                TextEntry::make('amount')
                                    ->label('Amount'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('payment_method')
                                    ->label('Payment Method'),
                                TextEntry::make('status')
                                    ->label('Status'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('transaction_id')
                                    ->label('Transaction ID'),
                                TextEntry::make('payment_date')
                                    ->label('Payment Date')
                                    ->date(),
                            ]),
                        TextEntry::make('description')
                            ->label('Description'),
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
