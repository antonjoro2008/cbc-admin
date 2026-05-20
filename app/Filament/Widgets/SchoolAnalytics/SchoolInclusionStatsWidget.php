<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesSchoolAnalytics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolInclusionStatsWidget extends StatsOverviewWidget
{
    use ResolvesSchoolAnalytics;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Gender & inclusion';
    }

    protected function getDescription(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $gr = $this->schoolInclusionView()['gender_reporting'] ?? [];

        return [
            Stat::make('Gender reporting', number_format($gr['reporting_rate_percent'] ?? 0, 1).'%'),
            Stat::make('Recorded', (string) ($gr['learners_with_gender'] ?? 0)),
            Stat::make('Not recorded', (string) ($gr['learners_without_gender'] ?? 0)),
        ];
    }
}
