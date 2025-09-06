<?php

namespace App\Filament\Resources\TokenTransactions;

use App\Filament\Resources\TokenTransactions\Pages\CreateTokenTransaction;
use App\Filament\Resources\TokenTransactions\Pages\EditTokenTransaction;
use App\Filament\Resources\TokenTransactions\Pages\ListTokenTransactions;
use App\Filament\Resources\TokenTransactions\Pages\ViewTokenTransaction;
use App\Filament\Resources\TokenTransactions\Schemas\TokenTransactionForm;
use App\Filament\Resources\TokenTransactions\Schemas\TokenTransactionInfolist;
use App\Filament\Resources\TokenTransactions\Tables\TokenTransactionsTable;
use App\Models\TokenTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TokenTransactionResource extends Resource
{
    protected static ?string $model = TokenTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static string|UnitEnum|null $navigationGroup = 'Payments & Transactions';

    public static function form(Schema $schema): Schema
    {
        return TokenTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TokenTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TokenTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTokenTransactions::route('/'),
            'view' => ViewTokenTransaction::route('/{record}'),
        ];
    }
}