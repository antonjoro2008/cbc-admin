<?php

namespace App\Filament\Widgets\Analytics;

use App\Filament\Widgets\Concerns\ResolvesStudentAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentOverviewStatsWidget extends StatsOverviewWidget
{
    use ResolvesStudentAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        $profile = $this->studentAnalytics()['profile'] ?? [];

        return $profile['name'] ?? 'Learner overview';
    }

    protected function getDescription(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $overview = $this->studentAnalytics()['overview'] ?? [];

        if ($overview === []) {
            return [
                Stat::make('Completed attempts', '0')
                    ->description('No assessment activity yet'),
            ];
        }

        return [
            Stat::make('Average score', number_format((float) ($overview['average_percent'] ?? 0), 1).'%')
                ->description($overview['competency_level'] ?? '—'),
            Stat::make('Completed attempts', (string) ($overview['total_completed_attempts'] ?? 0)),
            Stat::make('Distinct assessments', (string) ($overview['distinct_assessments'] ?? 0)),
            Stat::make('Trend', ucfirst((string) ($overview['improvement_trend'] ?? 'stable')))
                ->description(($overview['improvement_delta_percent'] ?? 0).'% vs prior attempt'),
        ];
    }
}
