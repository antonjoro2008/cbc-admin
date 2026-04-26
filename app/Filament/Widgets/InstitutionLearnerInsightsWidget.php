<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\InstitutionLearnerAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class InstitutionLearnerInsightsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -40;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    protected function getHeading(): ?string
    {
        return 'Learners & learning outcomes';
    }

    protected function getDescription(): ?string
    {
        return 'CBC-style CBE descriptors, roster coverage, and recent activity for every learner at your institution.';
    }

    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $service = app(InstitutionLearnerAnalyticsService::class);
        $cohort = $service->learnerCohortAnalytics((int) $user->institution_id, null, 800);
        $extras = $service->institutionAdminExtras((int) $user->institution_id);
        $summary = $extras['summary'];
        $insights = $cohort['insights'];
        $reporting = $cohort['inclusion_metrics']['gender_reporting'];

        return [
            Stat::make('Registered learners', (string) $summary['learners'])
                ->description('Students on your institution account')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Teachers', (string) $summary['teachers'])
                ->description('Accounts with teacher access')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Classrooms', (string) $summary['classrooms'])
                ->description('Teaching groups / classes')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Overall average score', $summary['learners'] > 0 ? $insights['average_percent'].'%' : '—')
                ->description($insights['average_level'])
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Learners trending up', $insights['learners_improving_percent'].'%')
                ->description('Share of learners with multiple attempts whose latest raw score improved')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Completed attempts (30 days)', (string) $summary['completed_attempts_last_30_days'])
                ->description($summary['distinct_learners_active_last_30_days'].' distinct learners active')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('Gender data on file', $reporting['reporting_rate_percent'].'%')
                ->description($reporting['learners_with_gender'].' learners with a recorded gender')
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),

            Stat::make('Guardian email on file', $summary['guardian_email_coverage_percent'].'%')
                ->description($summary['learners_with_guardian_email'].' learners with a guardian email')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('success'),
        ];
    }
}
