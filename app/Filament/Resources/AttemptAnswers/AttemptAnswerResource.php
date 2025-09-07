<?php

namespace App\Filament\Resources\AttemptAnswers;

use App\Filament\Resources\AttemptAnswers\Pages\CreateAttemptAnswer;
use App\Filament\Resources\AttemptAnswers\Pages\EditAttemptAnswer;
use App\Filament\Resources\AttemptAnswers\Pages\ListAttemptAnswers;
use App\Filament\Resources\AttemptAnswers\Pages\ViewAttemptAnswer;
use App\Filament\Resources\AttemptAnswers\Schemas\AttemptAnswerForm;
use App\Filament\Resources\AttemptAnswers\Schemas\AttemptAnswerInfolist;
use App\Filament\Resources\AttemptAnswers\Tables\AttemptAnswersTable;
use App\Filament\Resources\AttemptAnswers\RelationManagers\FeedbackRelationManager;
use App\Models\AttemptAnswer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AttemptAnswerResource extends Resource
{
    protected static ?string $model = AttemptAnswer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return AttemptAnswerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttemptAnswerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttemptAnswersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FeedbackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttemptAnswers::route('/'),
            'view' => ViewAttemptAnswer::route('/{record}'),
        ];
    }
}