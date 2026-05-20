<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Resources\Concerns\DisplaysRecordAnalytics;
use App\Filament\Resources\Institutions\InstitutionResource;
use App\Filament\Support\InstitutionAnalyticsWidgets;
use App\Filament\Widgets\SchoolAnalytics\SchoolActionItemsWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolAssessmentUsageTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolClassroomsTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolCompetencyAreasTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolGenderOutcomesTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolGenderRosterTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolInclusionStatsWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolLearnersTableWidget;
use App\Filament\Widgets\SchoolAnalytics\SchoolOverviewStatsWidget;
use App\Services\DashboardAnalyticsService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class ViewInstitution extends ViewRecord
{
    use DisplaysRecordAnalytics;

    protected static string $resource = InstitutionResource::class;

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
            'institutionId' => (int) $this->getRecord()->getKey(),
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getRecordAnalyticsWidgets(): array
    {
        $institutionId = (int) $this->getRecord()->getKey();
        $analytics = app(DashboardAnalyticsService::class)->institutionAnalyticsById($institutionId);
        $learnerCount = (int) ($analytics['summary']['learners'] ?? 0);

        if ($learnerCount === 0) {
            return [];
        }

        $widgets = InstitutionAnalyticsWidgets::forInstitution($institutionId);
        $widgets[] = SchoolOverviewStatsWidget::class;
        $widgets[] = SchoolLearnersTableWidget::class;
        $widgets[] = SchoolInclusionStatsWidget::class;
        $widgets[] = SchoolGenderRosterTableWidget::class;
        $widgets[] = SchoolGenderOutcomesTableWidget::class;

        if (! empty($analytics['classroom_breakdown'])) {
            $widgets[] = SchoolClassroomsTableWidget::class;
        }

        if (! empty($analytics['category_breakdown'])) {
            $widgets[] = SchoolCompetencyAreasTableWidget::class;
        }

        if (! empty($analytics['assessment_usage'])) {
            $widgets[] = SchoolAssessmentUsageTableWidget::class;
        }

        if (! empty($analytics['action_items'])) {
            $widgets[] = SchoolActionItemsWidget::class;
        }

        return $widgets;
    }
}
