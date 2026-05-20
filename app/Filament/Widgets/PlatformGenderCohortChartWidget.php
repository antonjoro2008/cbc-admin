<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformGenderCohortChartWidget extends ChartWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -82;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Students by gender';

    protected ?string $description = 'All learners on the platform with recorded gender (inclusion roster view).';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['gender_cohort'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'data' => $chart['values'],
                    'backgroundColor' => $chart['colors'],
                ],
            ],
        ];
    }
}
