<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\RendersCategoryCompetencyTable;
use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolCompetencyAreasTableWidget extends TableWidget
{
    use RendersCategoryCompetencyTable;
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $records = collect($this->institutionAnalytics()['category_breakdown'] ?? [])
            ->sortByDesc('average_percent')
            ->values()
            ->all();

        return $this->categoryCompetencyTable(
            $table,
            'Competency areas',
            '',
            $records,
            'desc',
        );
    }
}
