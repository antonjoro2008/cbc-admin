<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\RendersCategoryCompetencyTable;
use App\Filament\Widgets\Concerns\ResolvesSchoolAnalytics;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SchoolCompetencyAreasTableWidget extends TableWidget
{
    use RendersCategoryCompetencyTable;
    use ResolvesSchoolAnalytics;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $records = collect($this->schoolAnalytics()['category_breakdown'] ?? [])
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
