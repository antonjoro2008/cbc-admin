<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\AdminOnlyWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatisticsOverview extends StatsOverviewWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = -95;

    protected int | string | array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Platform overview';
    }

    protected function getDescription(): ?string
    {
        return 'Key metrics across all users, schools, students, and assessments.';
    }

    protected function getStats(): array
    {
        $data = app(DashboardAnalyticsService::class)->adminAnalytics();
        $overview = $data['overview'];
        $inclusion = $data['inclusion_metrics']['gender_reporting'] ?? [];

        return [
            Stat::make('Total students', (string) $overview['total_students'])
                ->description($overview['institution_students'].' in schools · '.$overview['individual_students'].' individual')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Schools / institutions', (string) $overview['total_institutions'])
                ->description(($overview['total_classrooms'] ?? 0).' classrooms platform-wide')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Platform average', $overview['platform_average_percent'].'%')
                ->description($overview['platform_competency_level'])
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Learners trending up', ($overview['learners_improving_percent'] ?? 0).'%')
                ->description('Share with multiple attempts whose latest score improved')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Completed attempts', (string) $overview['total_completed_attempts'])
                ->description($overview['attempts_last_30_days'].' in 30 days · '.($overview['distinct_learners_active_last_30_days'] ?? 0).' active learners')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Guardian email coverage', ($overview['guardian_email_coverage_percent'] ?? 0).'%')
                ->description(($overview['learners_with_guardian_email'] ?? 0).' learners with guardian email on file')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),

            Stat::make('Teachers', (string) $overview['total_teachers'])
                ->description($overview['total_parents'].' parents registered')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray'),

            Stat::make('Gender data coverage', ($inclusion['reporting_rate_percent'] ?? 0).'%')
                ->description(($inclusion['learners_with_gender'] ?? 0).' learners with gender recorded')
                ->descriptionIcon('heroicon-m-identification')
                ->color('danger'),

            Stat::make('Total revenue', 'KES '.number_format($overview['total_revenue_kes'], 2))
                ->description($overview['successful_payments'].' successful payments')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
