<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformCategoryBreakdownWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -69;

    protected string $view = 'filament.widgets.platform-category-breakdown';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $data = app(DashboardAnalyticsService::class)->adminAnalytics();

        return [
            'strengths' => $data['platform_strengths'] ?? [],
            'weaknesses' => $data['platform_weaknesses'] ?? [],
        ];
    }
}
