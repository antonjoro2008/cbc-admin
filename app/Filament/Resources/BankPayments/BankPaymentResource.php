<?php

namespace App\Filament\Resources\BankPayments;

use App\Filament\Resources\BankPayments\Pages\CreateBankPayment;
use App\Filament\Resources\BankPayments\Pages\EditBankPayment;
use App\Filament\Resources\BankPayments\Pages\ListBankPayments;
use App\Filament\Resources\BankPayments\Pages\ViewBankPayment;
use App\Filament\Resources\BankPayments\Schemas\BankPaymentForm;
use App\Filament\Resources\BankPayments\Schemas\BankPaymentInfolist;
use App\Filament\Resources\BankPayments\Tables\BankPaymentsTable;
use App\Models\BankPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BankPaymentResource extends Resource
{
    protected static ?string $model = BankPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static string|UnitEnum|null $navigationGroup = 'Payments & Transactions';

    public static function form(Schema $schema): Schema
    {
        return BankPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BankPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankPaymentsTable::configure($table);
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
            'index' => ListBankPayments::route('/'),
            'view' => ViewBankPayment::route('/{record}'),
        ];
    }
}