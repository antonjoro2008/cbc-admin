<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesSubjectAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class SubjectCompetencyDistributionChartWidget extends ChartWidget
{
    use ResolvesSubjectAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'CBE distribution';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $dist = $this->subjectAnalytics()['competency_distribution'] ?? ['BE' => 0, 'AE' => 0, 'ME' => 0, 'EE' => 0];

        return [
            'labels' => ['Below (BE)', 'Approaching (AE)', 'Meeting (ME)', 'Exceeding (EE)'],
            'datasets' => [
                [
                    'label' => 'Attempts',
                    'data' => array_values($dist),
                    'backgroundColor' => GravityCbcColors::competencyBands(),
                ],
            ],
        ];
    }
}
