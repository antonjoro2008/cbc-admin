<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesAssessmentAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class AssessmentCompletionChartWidget extends ChartWidget
{
    use ResolvesAssessmentAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Completion status';

    protected ?string $description = 'Completed attempts vs still in progress.';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $chart = $this->assessmentAnalytics()['charts']['completion_vs_dropout'] ?? [];

        return [
            'labels' => $chart['labels'] ?? ['Completed', 'In progress / dropped'],
            'datasets' => [
                [
                    'data' => $chart['values'] ?? [0, 0],
                    'backgroundColor' => [GravityCbcColors::BLUE, GravityCbcColors::BLUE_LIGHT],
                ],
            ],
        ];
    }
}
