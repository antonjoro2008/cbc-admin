<?php

namespace App\Filament\Resources\AssessmentAttempts;

use App\Filament\Resources\AssessmentAttempts\Pages\CreateAssessmentAttempt;
use App\Filament\Resources\AssessmentAttempts\Pages\EditAssessmentAttempt;
use App\Filament\Resources\AssessmentAttempts\Pages\ListAssessmentAttempts;
use App\Filament\Resources\AssessmentAttempts\Pages\ViewAssessmentAttempt;
use App\Filament\Resources\AssessmentAttempts\Schemas\AssessmentAttemptForm;
use App\Filament\Resources\AssessmentAttempts\Schemas\AssessmentAttemptInfolist;
use App\Filament\Resources\AssessmentAttempts\Tables\AssessmentAttemptsTable;
use App\Filament\Resources\AssessmentAttempts\RelationManagers\AttemptAnswersRelationManager;
use App\Filament\Resources\AssessmentAttempts\RelationManagers\TokenUsageRelationManager;
use App\Models\AssessmentAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssessmentAttemptResource extends Resource
{
    protected static ?string $model = AssessmentAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return AssessmentAttemptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssessmentAttemptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentAttemptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttemptAnswersRelationManager::class,
            TokenUsageRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentAttempts::route('/'),
            'view' => ViewAssessmentAttempt::route('/{record}'),
        ];
    }
}