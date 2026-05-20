<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesTeacherAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class TeacherSubjectPerformanceChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesTeacherAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Performance by subject';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->teacherAnalytics()['charts']['subject_performance'] ?? [];

        return $this->barDatasetFromChart($chart, 'Avg %', GravityCbcColors::rgbaGreen(0.75));
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
