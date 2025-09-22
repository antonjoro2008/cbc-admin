<?php

namespace App\Filament\Resources\AnswerMedia;

use App\Filament\Resources\AnswerMedia\Pages\CreateAnswerMedia;
use App\Filament\Resources\AnswerMedia\Pages\EditAnswerMedia;
use App\Filament\Resources\AnswerMedia\Pages\ListAnswerMedia;
use App\Filament\Resources\AnswerMedia\Pages\ViewAnswerMedia;
use App\Filament\Resources\AnswerMedia\Schemas\AnswerMediaForm;
use App\Filament\Resources\AnswerMedia\Schemas\AnswerMediaInfolist;
use App\Filament\Resources\AnswerMedia\Tables\AnswerMediaTable;
use App\Models\AnswerMedia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AnswerMediaResource extends Resource
{
    protected static ?string $model = AnswerMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
    protected static string|UnitEnum|null $navigationGroup = 'Questions & Answers';

    public static function form(Schema $schema): Schema
    {
        return AnswerMediaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnswerMediaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnswerMediaTable::configure($table);
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
            'index' => ListAnswerMedia::route('/'),
            'create' => CreateAnswerMedia::route('/create'),
            'view' => ViewAnswerMedia::route('/{record}'),
            'edit' => EditAnswerMedia::route('/{record}/edit'),
        ];
    }
}