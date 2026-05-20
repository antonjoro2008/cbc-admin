<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Filament\Widgets\Concerns\RendersCategoryCompetencyTable;
use App\Services\DashboardAnalyticsService;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformCompetencyStrengthsTableWidget extends TableWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;
    use RendersCategoryCompetencyTable;

    protected static ?int $sort = -69;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $records = app(DashboardAnalyticsService::class)->adminAnalytics()['platform_strengths'] ?? [];

        return $this->categoryCompetencyTable(
            $table,
            'Platform competency strengths',
            'Highest-scoring category tags from marked answers across all schools.',
            $records,
            'desc',
            'success',
        );
    }
}
