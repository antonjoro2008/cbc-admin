<?php

namespace App\Filament\Resources\Assessments\Pages;

use App\Filament\Resources\Assessments\AssessmentResource;
use App\Filament\Resources\Concerns\DisplaysRecordAnalytics;
use App\Filament\Widgets\Analytics\AssessmentCompletionChartWidget;
use App\Filament\Widgets\Analytics\AssessmentOverviewStatsWidget;
use App\Filament\Widgets\Analytics\AssessmentPassFailChartWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class ViewAssessment extends ViewRecord
{
    use DisplaysRecordAnalytics;

    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRecordAnalyticsWidgetData(): array
    {
        return [
            'assessmentId' => (int) $this->getRecord()->getKey(),
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getRecordAnalyticsWidgets(): array
    {
        $overview = app(DashboardAnalyticsService::class)
            ->assessmentAnalyticsById((int) $this->getRecord()->getKey())['overview'] ?? [];

        if (($overview['total_attempts'] ?? 0) === 0) {
            return [
                AssessmentOverviewStatsWidget::class,
            ];
        }

        return [
            AssessmentOverviewStatsWidget::class,
            AssessmentPassFailChartWidget::class,
            AssessmentCompletionChartWidget::class,
        ];
    }
}
