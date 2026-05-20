<?php

namespace App\Filament\Support;

use App\Filament\Widgets\Analytics\InstitutionActivityTrendChartWidget;
use App\Filament\Widgets\Analytics\InstitutionCategoryPerformanceChartWidget;
use App\Filament\Widgets\Analytics\InstitutionClassroomComparisonChartWidget;
use App\Filament\Widgets\Analytics\InstitutionCompetencyDistributionChartWidget;
use App\Filament\Widgets\Analytics\InstitutionSubjectPerformanceChartWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolStudentInsightsWidget;
use App\Services\DashboardAnalyticsService;

/**
 * Shared institution chart widgets for School analytics and Institution view pages.
 */
final class InstitutionAnalyticsWidgets
{
    /**
     * @return list<class-string>
     */
    public static function forInstitution(int $institutionId): array
    {
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalyticsById($institutionId);
        $learnerCount = (int) ($analytics['summary']['learners'] ?? 0);

        if ($learnerCount === 0) {
            return [];
        }

        $widgets = [
            InstitutionActivityTrendChartWidget::class,
            InstitutionSubjectPerformanceChartWidget::class,
            InstitutionCompetencyDistributionChartWidget::class,
            SchoolStudentInsightsWidget::class,
        ];

        if (! empty($analytics['classroom_breakdown'])) {
            $widgets[] = InstitutionClassroomComparisonChartWidget::class;
        }

        if (! empty($analytics['category_breakdown'])) {
            $widgets[] = InstitutionCategoryPerformanceChartWidget::class;
        }

        return $widgets;
    }
}
