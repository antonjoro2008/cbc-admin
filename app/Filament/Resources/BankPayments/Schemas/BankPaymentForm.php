<?php

namespace App\Filament\Resources\BankPayments\Schemas;

use App\Models\Payment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class BankPaymentForm
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
                        TextInput::make('bank_reference')
                            ->label('Bank Reference')
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('account_number')
                            ->label('Account Number')
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
