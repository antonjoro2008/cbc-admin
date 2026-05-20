<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformGenderPerformanceChartWidget extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -81;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Average performance by gender';

    protected ?string $description = 'Mean score % from completed attempts, segmented by recorded gender.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['gender_performance'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Average %',
                    'data' => $chart['values'],
                    'backgroundColor' => $chart['colors'],
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                ],
            ],
        ];
    }
}
