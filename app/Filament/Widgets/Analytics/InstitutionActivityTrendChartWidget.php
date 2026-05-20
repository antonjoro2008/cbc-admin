<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class InstitutionActivityTrendChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Assessment activity (14 days)';

    protected ?string $description = 'Completed attempts by learners at this school.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = $this->institutionAnalytics()['charts']['activity_last_14_days'] ?? [];

        return [
            'labels' => $chart['labels'] ?? [],
            'datasets' => [
                [
                    'label' => 'Completed assessments',
                    'data' => $chart['values'] ?? [],
                    'borderColor' => GravityCbcColors::GREEN,
                    'backgroundColor' => GravityCbcColors::rgbaGreen(0.12),
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }
}
