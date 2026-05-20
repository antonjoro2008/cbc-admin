<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesSubjectAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class SubjectAssessmentPerformanceChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesSubjectAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Performance by assessment';

    protected ?string $description = 'Average completed score % per assessment in this subject.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->subjectAnalytics()['charts']['assessment_performance'] ?? [];

        return $this->barDatasetFromChart($chart, 'Avg %', GravityCbcColors::rgbaBlue(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
