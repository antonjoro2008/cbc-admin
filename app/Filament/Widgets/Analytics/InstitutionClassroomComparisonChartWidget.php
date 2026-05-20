<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class InstitutionClassroomComparisonChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Performance by classroom';

    protected ?string $description = 'Average CBE outcomes per class.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->institutionAnalytics()['charts']['classroom_comparison'] ?? [];

        return $this->barDatasetFromChart($chart, 'Average %', GravityCbcColors::rgbaBlue(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
