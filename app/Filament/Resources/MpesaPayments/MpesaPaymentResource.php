<?php

namespace App\Filament\Resources\MpesaPayments;

use App\Filament\Resources\MpesaPayments\Pages\CreateMpesaPayment;
use App\Filament\Resources\MpesaPayments\Pages\EditMpesaPayment;
use App\Filament\Resources\MpesaPayments\Pages\ListMpesaPayments;
use App\Filament\Resources\MpesaPayments\Pages\ViewMpesaPayment;
use App\Filament\Resources\MpesaPayments\Schemas\MpesaPaymentForm;
use App\Filament\Resources\MpesaPayments\Schemas\MpesaPaymentInfolist;
use App\Filament\Resources\MpesaPayments\Tables\MpesaPaymentsTable;
use App\Models\MpesaPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MpesaPaymentResource extends Resource
{
    protected static ?string $model = MpesaPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;
    protected static string|UnitEnum|null $navigationGroup = 'Payments & Transactions';

    public static function form(Schema $schema): Schema
    {
        return MpesaPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MpesaPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MpesaPaymentsTable::configure($table);
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
            'index' => ListMpesaPayments::route('/'),
            'view' => ViewMpesaPayment::route('/{record}'),
        ];
    }
}