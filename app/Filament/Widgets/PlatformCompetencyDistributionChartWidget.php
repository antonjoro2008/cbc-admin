<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformCompetencyDistributionChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

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
                    'backgroundColor' => GravityCbcColors::competencyBands(),
                ],
            ],
        ];
    }
}
