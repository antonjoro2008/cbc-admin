<?php

namespace App\Filament\Resources\VAssessmentAttemptsReports;

use App\Filament\Resources\VAssessmentAttemptsReports\Pages\CreateVAssessmentAttemptsReport;
use App\Filament\Resources\VAssessmentAttemptsReports\Pages\EditVAssessmentAttemptsReport;
use App\Filament\Resources\VAssessmentAttemptsReports\Pages\ListVAssessmentAttemptsReports;
use App\Filament\Resources\VAssessmentAttemptsReports\Schemas\VAssessmentAttemptsReportForm;
use App\Filament\Resources\VAssessmentAttemptsReports\Tables\VAssessmentAttemptsReportsTable;
use App\Models\VAssessmentAttemptsReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VAssessmentAttemptsReportResource extends Resource
{
    protected static ?string $model = VAssessmentAttemptsReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $recordTitleAttribute = 'Assessments Report';
    protected static ?string $pluralModelLabel = 'Assessment Attempts Reports';
    protected static ?string $modelLabel = 'Assessment Attempts Report';
    protected static ?string $navigationLabel = 'Assessment Attempts Reports';

    public static function form(Schema $schema): Schema
    {
        // return VAssessmentAttemptsReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VAssessmentAttemptsReportsTable::configure($table);
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
            'index' => ListVAssessmentAttemptsReports::route('/'),
        ];
    }
}
