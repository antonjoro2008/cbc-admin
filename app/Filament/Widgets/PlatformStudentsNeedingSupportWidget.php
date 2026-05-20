<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformStudentsNeedingSupportWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -64;

    protected string $view = 'filament.widgets.platform-students-table';

    protected int | string | array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'heading' => 'Students needing support',
            'description' => 'Below 50% average — candidates for intervention.',
            'students' => app(DashboardAnalyticsService::class)->platformStudentSummaries(15)['support'],
            'variant' => 'support',
        ];
    }
}
