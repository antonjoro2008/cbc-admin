<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolOverviewStatsWidget extends StatsOverviewWidget
{
    use ResolvesInstitutionAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        $name = $this->institutionAnalytics()['institution']['name'] ?? null;

        return $name ? $name : 'School overview';
    }

    protected function getDescription(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $analytics = $this->institutionAnalytics();

        if ($analytics === []) {
            return [];
        }

        $summary = $analytics['summary'] ?? [];
        $insights = $analytics['insights'] ?? [];

        return [
            Stat::make('Learners', (string) ($summary['learners'] ?? 0)),
            Stat::make('Teachers', (string) ($summary['teachers'] ?? 0)),
            Stat::make('Classrooms', (string) ($summary['classrooms'] ?? 0)),
            Stat::make('Average score', ($summary['learners'] ?? 0) > 0
                ? number_format((float) ($insights['average_percent'] ?? 0), 1).'%'
                : '—'),
            Stat::make('CBE level', $insights['average_level'] ?? '—'),
            Stat::make('Trending up', ($insights['learners_improving_percent'] ?? 0).'%'),
            Stat::make('Attempts (30d)', (string) ($summary['completed_attempts_last_30_days'] ?? 0)),
            Stat::make('Guardian email', number_format((float) ($summary['guardian_email_coverage_percent'] ?? 0), 1).'%'),
        ];
    }
}
