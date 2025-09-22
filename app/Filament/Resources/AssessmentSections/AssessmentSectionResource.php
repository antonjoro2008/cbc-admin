<?php

namespace App\Filament\Resources\AssessmentSections;

use App\Filament\Resources\AssessmentSections\Pages\CreateAssessmentSection;
use App\Filament\Resources\AssessmentSections\Pages\EditAssessmentSection;
use App\Filament\Resources\AssessmentSections\Pages\ListAssessmentSections;
use App\Filament\Resources\AssessmentSections\Pages\ViewAssessmentSection;
use App\Filament\Resources\AssessmentSections\Schemas\AssessmentSectionForm;
use App\Filament\Resources\AssessmentSections\Schemas\AssessmentSectionInfolist;
use App\Filament\Resources\AssessmentSections\Tables\AssessmentSectionsTable;
use App\Filament\Resources\AssessmentSections\RelationManagers\QuestionsRelationManager;
use App\Models\AssessmentSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssessmentSectionResource extends Resource
{
    protected static ?string $model = AssessmentSection::class;

    protected static ?string $navigationLabel = 'Sections';

    protected static ?string $modelLabel = 'Section';

    protected static ?string $pluralModelLabel = 'Sections';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Assessments';

    public static function form(Schema $schema): Schema
    {
        return AssessmentSectionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssessmentSectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentSectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessmentSections::route('/'),
            'create' => CreateAssessmentSection::route('/create'),
            'view' => ViewAssessmentSection::route('/{record}'),
            'edit' => EditAssessmentSection::route('/{record}/edit'),
        ];
    }
}