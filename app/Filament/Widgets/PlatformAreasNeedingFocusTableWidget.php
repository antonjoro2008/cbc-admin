<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Filament\Widgets\Concerns\RendersCategoryCompetencyTable;
use App\Services\DashboardAnalyticsService;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlatformAreasNeedingFocusTableWidget extends TableWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;
    use RendersCategoryCompetencyTable;

    protected static ?int $sort = -68;

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $records = app(DashboardAnalyticsService::class)->adminAnalytics()['platform_weaknesses'] ?? [];

        return $this->categoryCompetencyTable(
            $table,
            'Platform areas needing focus',
            'Lowest-scoring competency tags — use for platform-wide intervention planning.',
            $records,
            'asc',
            'warning',
        );
    }
}
