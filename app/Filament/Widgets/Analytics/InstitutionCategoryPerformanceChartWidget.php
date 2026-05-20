<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class InstitutionCategoryPerformanceChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by competency area';

    protected ?string $description = 'Average scores from marked answers, grouped by category tag.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->institutionAnalytics()['charts']['category_performance'] ?? [];

        return $this->barDatasetFromChart($chart, 'Average %', GravityCbcColors::rgbaBlue(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
