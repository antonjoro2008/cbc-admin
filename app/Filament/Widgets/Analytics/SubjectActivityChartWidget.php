<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesSubjectAnalytics;
use Filament\Widgets\ChartWidget;

class SubjectActivityChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesSubjectAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Activity (14 days)';

    protected ?string $description = 'Completed attempts on assessments in this subject.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = $this->subjectAnalytics()['charts']['activity_last_14_days'] ?? [];

        return $this->lineDatasetFromChart($chart, 'Completed');
    }
}
