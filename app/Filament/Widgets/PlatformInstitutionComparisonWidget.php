<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformInstitutionComparisonWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -70;

    protected string $view = 'filament.widgets.platform-institution-table';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'institutions' => app(DashboardAnalyticsService::class)->platformInstitutionBreakdown(50),
        ];
    }
}
