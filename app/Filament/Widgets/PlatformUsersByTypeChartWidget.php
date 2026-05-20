<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformUsersByTypeChartWidget extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -84;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Users by role';

    protected ?string $description = 'Distribution of registered account types.';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['users_by_type'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'data' => $chart['values'],
                    'backgroundColor' => ['#705EBC', '#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
                ],
            ],
        ];
    }
}
