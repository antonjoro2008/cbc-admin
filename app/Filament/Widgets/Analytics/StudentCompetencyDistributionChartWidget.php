<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesStudentAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class StudentCompetencyDistributionChartWidget extends ChartWidget
{
    use ResolvesStudentAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'CBE competency distribution';

    protected ?string $description = 'This learner\'s completed attempts by CBE level.';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $dist = $this->studentAnalytics()['competency_distribution'] ?? ['BE' => 0, 'AE' => 0, 'ME' => 0, 'EE' => 0];

        return [
            'labels' => ['Below (BE)', 'Approaching (AE)', 'Meeting (ME)', 'Exceeding (EE)'],
            'datasets' => [
                [
                    'data' => array_values($dist),
                    'backgroundColor' => GravityCbcColors::competencyBands(),
                ],
            ],
        ];
    }
}
