<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformTokensPieChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -84;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Tokens purchased vs used';

    protected ?string $description = 'Platform-wide token credits compared to consumption.';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['tokens_purchased_vs_used'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'data' => $chart['values'],
                    'backgroundColor' => [GravityCbcColors::GREEN, GravityCbcColors::RED],
                ],
            ],
        ];
    }
}
