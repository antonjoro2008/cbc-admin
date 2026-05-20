<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformCompetencyDistributionChartWidget extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -83;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'CBE competency distribution';

    protected ?string $description = 'Completed attempts by CBC/CBE level (BE, AE, ME, EE).';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['competency_distribution'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Attempts',
                    'data' => $chart['values'],
                    'backgroundColor' => ['#EF4444', '#F59E0B', '#3B82F6', '#10B981'],
                ],
            ],
        ];
    }
}
