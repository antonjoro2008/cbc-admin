<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class PaymentForm
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
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'mpesa' => 'M-Pesa',
                                'bank' => 'Bank Transfer',
                                'card' => 'Credit/Debit Card',
                                'cash' => 'Cash',
                            ])
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                    ]),
                Grid::make(2)
                    ->schema([
                        TextInput::make('transaction_id')
                            ->label('Transaction ID')
                            ->required(),
                        DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->required(),
                    ]),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
