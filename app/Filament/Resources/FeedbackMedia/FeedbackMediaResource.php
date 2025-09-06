<?php

namespace App\Filament\Resources\FeedbackMedia;

use App\Filament\Resources\FeedbackMedia\Pages\CreateFeedbackMedia;
use App\Filament\Resources\FeedbackMedia\Pages\EditFeedbackMedia;
use App\Filament\Resources\FeedbackMedia\Pages\ListFeedbackMedia;
use App\Filament\Resources\FeedbackMedia\Pages\ViewFeedbackMedia;
use App\Filament\Resources\FeedbackMedia\Schemas\FeedbackMediaForm;
use App\Filament\Resources\FeedbackMedia\Schemas\FeedbackMediaInfolist;
use App\Filament\Resources\FeedbackMedia\Tables\FeedbackMediaTable;
use App\Models\FeedbackMedia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeedbackMediaResource extends Resource
{
    protected static ?string $model = FeedbackMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
    protected static string|UnitEnum|null $navigationGroup = 'Questions & Answers';

    public static function form(Schema $schema): Schema
    {
        return FeedbackMediaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedbackMediaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedbackMediaTable::configure($table);
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
            'index' => ListFeedbackMedia::route('/'),
            'view' => ViewFeedbackMedia::route('/{record}'),
        ];
    }
}