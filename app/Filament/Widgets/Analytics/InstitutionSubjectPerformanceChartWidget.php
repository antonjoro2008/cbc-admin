<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class InstitutionSubjectPerformanceChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by subject';

    protected ?string $description = 'Average score % across learners, grouped by subject.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->institutionAnalytics()['charts']['subject_performance'] ?? [];

        return $this->barDatasetFromChart($chart, 'Average %', GravityCbcColors::rgbaGreen(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
