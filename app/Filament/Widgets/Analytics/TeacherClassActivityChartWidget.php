<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesTeacherAnalytics;
use Filament\Widgets\ChartWidget;

class TeacherClassActivityChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesTeacherAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Class activity (14 days)';

    protected ?string $description = 'Completed attempts by learners in this teacher\'s class.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $chart = $this->teacherAnalytics()['charts']['activity_last_14_days'] ?? [];

        return $this->lineDatasetFromChart($chart, 'Completed');
    }
}
