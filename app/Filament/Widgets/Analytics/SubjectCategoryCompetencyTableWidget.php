<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\RendersCategoryCompetencyTable;
use App\Filament\Widgets\Concerns\ResolvesSubjectAnalytics;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SubjectCategoryCompetencyTableWidget extends TableWidget
{
    use RendersCategoryCompetencyTable;
    use ResolvesSubjectAnalytics;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $records = collect($this->subjectAnalytics()['category_breakdown'] ?? [])
            ->sortByDesc('average_percent')
            ->values()
            ->all();

        return $this->categoryCompetencyTable(
            $table,
            'Performance by category',
            'Marked answers grouped by question category tag within this subject.',
            $records,
            'desc',
        );
    }
}
