<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesStudentAnalytics;
use Filament\Widgets\ChartWidget;

class StudentScoreTrendChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesStudentAnalytics;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Score trend (last attempts)';

    protected ?string $description = 'Recent completed assessment scores over time.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = $this->studentAnalytics()['charts']['score_trend_last_10'] ?? [];

        return $this->lineDatasetFromChart($chart, 'Score %');
    }

    protected function getOptions(): array
    {
        return $this->percentScaleOptions();
    }
}
