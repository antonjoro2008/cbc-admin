<?php

namespace App\Filament\Resources\TokenUsages;

use App\Filament\Resources\TokenUsages\Pages\CreateTokenUsage;
use App\Filament\Resources\TokenUsages\Pages\EditTokenUsage;
use App\Filament\Resources\TokenUsages\Pages\ListTokenUsages;
use App\Filament\Resources\TokenUsages\Pages\ViewTokenUsage;
use App\Filament\Resources\TokenUsages\Schemas\TokenUsageForm;
use App\Filament\Resources\TokenUsages\Schemas\TokenUsageInfolist;
use App\Filament\Resources\TokenUsages\Tables\TokenUsagesTable;
use App\Models\TokenUsage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TokenUsageResource extends Resource
{
    protected static ?string $model = TokenUsage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static string|UnitEnum|null $navigationGroup = 'Payments & Transactions';

    public static function form(Schema $schema): Schema
    {
        return TokenUsageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TokenUsageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TokenUsagesTable::configure($table);
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
            'index' => ListTokenUsages::route('/'),
            'view' => ViewTokenUsage::route('/{record}'),
        ];
    }
}