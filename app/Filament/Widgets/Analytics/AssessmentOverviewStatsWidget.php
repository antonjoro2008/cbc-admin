<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesAssessmentAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AssessmentOverviewStatsWidget extends StatsOverviewWidget
{
    use ResolvesAssessmentAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Assessment analytics';
    }

    protected function getDescription(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $overview = $this->assessmentAnalytics()['overview'] ?? [];

        if ($overview === []) {
            return [];
        }

        return [
            Stat::make('Total attempts', (string) ($overview['total_attempts'] ?? 0)),
            Stat::make('Completed', (string) ($overview['completed_attempts'] ?? 0)),
            Stat::make('Average score', number_format((float) ($overview['average_score_percent'] ?? 0), 1).'%'),
            Stat::make('Completion rate', ($overview['completion_rate_percent'] ?? 0).'%'),
            Stat::make('Difficulty', $overview['difficulty_label'] ?? '—'),
        ];
    }
}
