<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformGradeLevelChartWidget extends ChartWidget
{
    use AdminOnlyWidget;

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
                    'backgroundColor' => '#705EBC',
                ],
            ],
        ];
    }
}
