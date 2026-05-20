<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformClassroomComparisonWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -72;

    protected string $view = 'filament.widgets.platform-classroom-table';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'classrooms' => app(DashboardAnalyticsService::class)->platformClassroomBreakdown(50),
        ];
    }
}
