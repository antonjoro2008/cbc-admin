<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesAssessmentAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class AssessmentPassFailChartWidget extends ChartWidget
{
    use ResolvesAssessmentAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Pass vs fail';

    protected ?string $description = 'Completed attempts at ≥50% vs below 50%.';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $chart = $this->assessmentAnalytics()['charts']['pass_vs_fail'] ?? [];

        return [
            'labels' => $chart['labels'] ?? ['Pass (≥50%)', 'Fail (<50%)'],
            'datasets' => [
                [
                    'data' => $chart['values'] ?? [0, 0],
                    'backgroundColor' => [GravityCbcColors::GREEN, GravityCbcColors::RED],
                ],
            ],
        ];
    }
}
