<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\BuildsGravityChartData;
use App\Filament\Widgets\Concerns\ResolvesTeacherAnalytics;
use App\Support\GravityCbcColors;
use Filament\Widgets\ChartWidget;

class TeacherLearnerRankingChartWidget extends ChartWidget
{
    use BuildsGravityChartData;
    use ResolvesTeacherAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Learner ranking';

    protected ?string $description = 'Average score % by learner in this class (highest first).';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $chart = $this->teacherAnalytics()['charts']['learner_ranking'] ?? [];

        return $this->barDatasetFromChart($chart, 'Avg %', GravityCbcColors::rgbaBlue(0.75));
    }

    protected function getOptions(): array
    {
        return array_merge($this->percentScaleOptions(), [
            'indexAxis' => 'y',
        ]);
    }
}
