<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformUsersByTypeChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

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
                    'backgroundColor' => [
                        GravityCbcColors::GREEN,
                        GravityCbcColors::BLUE,
                        GravityCbcColors::BLUE_LIGHT,
                        GravityCbcColors::RED,
                        GravityCbcColors::GREEN,
                    ],
                ],
            ],
        ];
    }
}
