<?php

namespace App\Filament\Resources\QuestionMedia;

use App\Filament\Resources\QuestionMedia\Pages\CreateQuestionMedia;
use App\Filament\Resources\QuestionMedia\Pages\EditQuestionMedia;
use App\Filament\Resources\QuestionMedia\Pages\ListQuestionMedia;
use App\Filament\Resources\QuestionMedia\Pages\ViewQuestionMedia;
use App\Filament\Resources\QuestionMedia\Schemas\QuestionMediaForm;
use App\Filament\Resources\QuestionMedia\Schemas\QuestionMediaInfolist;
use App\Filament\Resources\QuestionMedia\Tables\QuestionMediaTable;
use App\Models\QuestionMedia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QuestionMediaResource extends Resource
{
    protected static ?string $model = QuestionMedia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;
    protected static string|UnitEnum|null $navigationGroup = 'Questions & Answers';

    public static function form(Schema $schema): Schema
    {
        return QuestionMediaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuestionMediaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionMediaTable::configure($table);
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
            'index' => ListQuestionMedia::route('/'),
            'create' => CreateQuestionMedia::route('/create'),
            'view' => ViewQuestionMedia::route('/{record}'),
            'edit' => EditQuestionMedia::route('/{record}/edit'),
        ];
    }
}