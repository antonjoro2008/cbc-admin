<?php

namespace App\Filament\Resources\MpesaPayments\Schemas;

use App\Models\Payment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class MpesaPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('payment_id')
                            ->label('Payment')
                            ->options(Payment::all()->pluck('transaction_id', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('mpesa_receipt_number')
                            ->label('M-Pesa Receipt Number')
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
                DateTimePicker::make('transaction_date')
                    ->label('Transaction Date')
                    ->required(),
            ]);
    }
}
