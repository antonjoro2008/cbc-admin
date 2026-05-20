<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesStudentAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class StudentSubjectPerformanceChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesStudentAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Performance by subject';

    protected ?string $description = 'Average score % per subject for this learner.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->studentAnalytics()['charts']['subject_performance'] ?? [];

        return $this->barDatasetFromChart($chart, 'Average %', GravityCbcColors::rgbaGreen(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
