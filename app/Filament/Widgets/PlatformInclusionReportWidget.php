<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformInclusionReportWidget extends Widget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -80;

    protected string $view = 'filament.widgets.inclusion-report';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $service = app(DashboardAnalyticsService::class);
        $inclusion = $service->adminAnalytics()['inclusion_metrics'];

        return array_merge($service->formatInclusionForView($inclusion), [
            'scope_label' => 'platform',
            'show_color_legend' => true,
            'heading' => 'Platform-wide inclusion & gender equity',
            'description' => 'All students across every school and individual accounts. Use for CBC reporting and equity reviews.',
        ]);
    }
}
