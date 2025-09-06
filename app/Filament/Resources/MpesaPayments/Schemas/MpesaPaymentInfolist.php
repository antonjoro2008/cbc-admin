<?php

namespace App\Filament\Resources\MpesaPayments\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class MpesaPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('M-Pesa Payment Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('payment.transaction_id')
                                    ->label('Payment Transaction'),
                                TextEntry::make('mpesa_receipt_number')
                                    ->label('M-Pesa Receipt Number'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('phone_number')
                                    ->label('Phone Number'),
                                TextEntry::make('amount')
                                    ->label('Amount'),
                            ]),
                        TextEntry::make('transaction_date')
                            ->label('Transaction Date')
                            ->dateTime(),
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
