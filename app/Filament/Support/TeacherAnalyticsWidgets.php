<?php

namespace App\Filament\Support;

use App\Filament\Widgets\Analytics\TeacherClassActivityChartWidget;
use App\Filament\Widgets\Analytics\TeacherCompetencyDistributionChartWidget;
use App\Filament\Widgets\Analytics\TeacherLearnerRankingChartWidget;
use App\Filament\Widgets\Analytics\TeacherOverviewStatsWidget;
use App\Filament\Widgets\Analytics\TeacherSubjectPerformanceChartWidget;
use App\Services\DashboardAnalyticsService;

final class TeacherAnalyticsWidgets
{
    /**
     * @return list<class-string>
     */
    public static function forTeacher(int $teacherId): array
    {
        $analytics = app(DashboardAnalyticsService::class)->teacherAnalyticsById($teacherId);

        if (isset($analytics['message'])) {
            return [TeacherOverviewStatsWidget::class];
        }

        $widgets = [
            TeacherOverviewStatsWidget::class,
            TeacherClassActivityChartWidget::class,
            TeacherLearnerRankingChartWidget::class,
            TeacherSubjectPerformanceChartWidget::class,
            TeacherCompetencyDistributionChartWidget::class,
        ];

        return $widgets;
    }
}
