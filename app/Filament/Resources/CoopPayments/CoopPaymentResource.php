<?php

namespace App\Filament\Resources\CoopPayments;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\CoopPayments\Pages\ListCoopPayments;
use App\Filament\Resources\CoopPayments\Pages\ViewCoopPayment;
use App\Filament\Resources\CoopPayments\Schemas\CoopPaymentInfolist;
use App\Filament\Resources\CoopPayments\Tables\CoopPaymentsTable;
use App\Models\CoopPayment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CoopPaymentResource extends BaseResource
{
    protected static ?string $model = CoopPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Payments & Transactions';

    protected static ?string $navigationLabel = 'Co-op Payments';

    protected static ?string $modelLabel = 'Co-op payment';

    public static function infolist(Schema $schema): Schema
    {
        return CoopPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoopPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoopPayments::route('/'),
            'view' => ViewCoopPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
