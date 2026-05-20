<?php

namespace App\Filament\Support;

use App\Filament\Widgets\Analytics\SubjectActivityChartWidget;
use App\Filament\Widgets\Analytics\SubjectAssessmentPerformanceChartWidget;
use App\Filament\Widgets\Analytics\SubjectCompetencyDistributionChartWidget;
use App\Filament\Widgets\Analytics\SubjectOverviewStatsWidget;
use App\Services\DashboardAnalyticsService;

final class SubjectAnalyticsWidgets
{
    /**
     * @return list<class-string>
     */
    public static function forSubject(int $subjectId): array
    {
        $overview = app(DashboardAnalyticsService::class)->subjectAnalyticsById($subjectId)['overview'] ?? [];

        if (($overview['total_assessments'] ?? 0) === 0) {
            return [SubjectOverviewStatsWidget::class];
        }

        return [
            SubjectOverviewStatsWidget::class,
            SubjectActivityChartWidget::class,
            SubjectAssessmentPerformanceChartWidget::class,
            SubjectCompetencyDistributionChartWidget::class,
        ];
    }
}
