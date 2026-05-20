<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesSubjectAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubjectOverviewStatsWidget extends StatsOverviewWidget
{
    use ResolvesSubjectAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        $name = $this->subjectAnalytics()['subject']['name'] ?? null;

        return $name ? 'Subject — '.$name : 'Subject overview';
    }

    protected function getStats(): array
    {
        $overview = $this->subjectAnalytics()['overview'] ?? [];

        return [
            Stat::make('Assessments', (string) ($overview['total_assessments'] ?? 0)),
            Stat::make('Learners', (string) ($overview['distinct_learners'] ?? 0)),
            Stat::make('Completed attempts', (string) ($overview['completed_attempts'] ?? 0)),
            Stat::make('Average score', number_format((float) ($overview['average_score_percent'] ?? 0), 1).'%'),
            Stat::make('Completion rate', ($overview['completion_rate_percent'] ?? 0).'%'),
        ];
    }
}
