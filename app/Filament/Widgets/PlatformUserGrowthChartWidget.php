<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformUserGrowthChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -86;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'User growth over time';

    protected ?string $description = 'New user registrations per month (last 6 months).';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['user_growth_over_time'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'New users',
                    'data' => $chart['values'],
                    'borderColor' => GravityCbcColors::BLUE,
                    'backgroundColor' => GravityCbcColors::rgbaBlue(0.12),
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }
}
