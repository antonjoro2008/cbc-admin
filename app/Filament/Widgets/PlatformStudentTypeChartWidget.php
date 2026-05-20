<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Filament\Widgets\Concerns\LazyAnalyticsWidget;
use App\Services\DashboardAnalyticsService;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class PlatformStudentTypeChartWidget extends ChartWidget
{
    use AdminOnlyWidget;
    use LazyAnalyticsWidget;

    protected static ?int $sort = -79;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Institution vs individual learners';

    protected ?string $description = 'Students enrolled through schools vs self-registered individuals.';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $chart = app(DashboardAnalyticsService::class)->adminAnalytics()['charts']['student_type_split'];

        return [
            'labels' => $chart['labels'],
            'datasets' => [
                [
                    'data' => $chart['values'],
                    'backgroundColor' => [GravityCbcColors::GREEN, GravityCbcColors::BLUE],
                ],
            ],
        ];
    }
}
