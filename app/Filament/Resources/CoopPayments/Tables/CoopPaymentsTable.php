<?php

namespace App\Filament\Resources\CoopPayments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoopPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment.reference')
                    ->label('Payment ref')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('message_reference')
                    ->label('Message reference')
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'successful' => 'success',
                        'pending' => 'warning',
                        'failed', 'ignored' => 'danger',
                        'unmatched' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('transaction_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
