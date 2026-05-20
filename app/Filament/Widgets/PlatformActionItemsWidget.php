<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\Widget;

class PlatformActionItemsWidget extends Widget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -55;

    protected string $view = 'filament.widgets.action-items';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $data = app(DashboardAnalyticsService::class)->adminAnalytics();

        return [
            'heading' => 'Recommended platform actions',
            'action_items' => $data['action_items'] ?? [],
        ];
    }
}
