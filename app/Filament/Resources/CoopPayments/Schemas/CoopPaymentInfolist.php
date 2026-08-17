<?php

namespace App\Filament\Resources\CoopPayments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CoopPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Co-op payment')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('payment.reference')
                                    ->label('Internal payment reference')
                                    ->placeholder('Unmatched'),
                                TextEntry::make('message_reference')
                                    ->label('STK message reference'),
                                TextEntry::make('transaction_id')
                                    ->label('Co-op transaction ID'),
                                TextEntry::make('payment_ref')
                                    ->label('IPN payment ref'),
                                TextEntry::make('phone_number')
                                    ->label('Phone number'),
                                TextEntry::make('account_number')
                                    ->label('Account number'),
                                TextEntry::make('amount')
                                    ->money('KES'),
                                TextEntry::make('currency'),
                                TextEntry::make('source')
                                    ->badge(),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('event_type')
                                    ->label('Event type'),
                                TextEntry::make('transaction_date')
                                    ->dateTime(),
                            ]),
                        TextEntry::make('narration')
                            ->columnSpanFull(),
                    ]),
                Section::make('Gateway payloads')
                    ->schema([
                        TextEntry::make('stk_response')
                            ->formatStateUsing(fn ($state) => self::prettyJson($state))
                            ->columnSpanFull(),
                        TextEntry::make('callback_payload')
                            ->formatStateUsing(fn ($state) => self::prettyJson($state))
                            ->columnSpanFull(),
                        TextEntry::make('ipn_payload')
                            ->formatStateUsing(fn ($state) => self::prettyJson($state))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function prettyJson(mixed $state): string
    {
        if (! is_array($state) || $state === []) {
            return '—';
        }

        return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—';
    }
}
