<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformGradeLevelChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -78;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Students by grade level';

    protected ?string $description = 'Roster distribution across all registered students.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['grade_level_distribution'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => $chart['values'],
                    'backgroundColor' => GravityCbcColors::GREEN,
                ],
            ],
        ];
    }
}
