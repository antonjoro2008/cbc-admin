<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformActivityChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -85;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Platform assessment activity';

    protected ?string $description = 'Completed attempts across all students and schools over the last 14 days.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['activity_last_14_days'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Completed attempts',
                    'data' => $chart['values'],
                    'borderColor' => GravityCbcColors::GREEN,
                    'backgroundColor' => GravityCbcColors::rgbaGreen(0.12),
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }
}
