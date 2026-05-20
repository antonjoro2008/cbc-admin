<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesSubjectAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class SubjectCategoryPerformanceChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesSubjectAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by category';

    protected ?string $description = 'Average scores from marked answers, grouped by question category tag (e.g. Biology, Chemistry, Physics for integrated science).';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->subjectAnalytics()['charts']['category_performance'] ?? [];

        return $this->barDatasetFromChart($chart, 'Average %', GravityCbcColors::rgbaRed(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
