<?php

namespace App\Filament\Support;

use App\Filament\Widgets\Analytics\SubjectActivityChartWidget;
use App\Filament\Widgets\Analytics\SubjectAssessmentPerformanceChartWidget;
use App\Filament\Widgets\Analytics\SubjectCategoryCompetencyTableWidget;
use App\Filament\Widgets\Analytics\SubjectCategoryPerformanceChartWidget;
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
        $analytics = app(DashboardAnalyticsService::class)->subjectAnalyticsById($subjectId);
        $overview = $analytics['overview'] ?? [];

        if (($overview['total_assessments'] ?? 0) === 0) {
            return [SubjectOverviewStatsWidget::class];
        }

        $widgets = [
            SubjectOverviewStatsWidget::class,
            SubjectActivityChartWidget::class,
            SubjectAssessmentPerformanceChartWidget::class,
            SubjectCompetencyDistributionChartWidget::class,
        ];

        if (! empty($analytics['category_breakdown'])) {
            $widgets[] = SubjectCategoryPerformanceChartWidget::class;
            $widgets[] = SubjectCategoryCompetencyTableWidget::class;
        }

        return $widgets;
    }
}
