<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformClassroomComparisonChartWidget extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -73;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by classroom (all schools)';

    protected ?string $description = 'Compare average CBE outcomes across every classroom on the platform.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['classroom_comparison'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => $chart['values'],
                    'backgroundColor' => 'rgba(20, 184, 166, 0.75)',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'max' => 100,
                ],
            ],
        ];
    }
}
