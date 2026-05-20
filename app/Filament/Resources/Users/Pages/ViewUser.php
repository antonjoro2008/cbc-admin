<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Concerns\DisplaysRecordAnalytics;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\InstitutionAnalyticsWidgets;
use App\Filament\Support\TeacherAnalyticsWidgets;
use App\Filament\Widgets\Analytics\StudentCompetencyDistributionChartWidget;
use App\Filament\Widgets\Analytics\StudentOverviewStatsWidget;
use App\Filament\Widgets\Analytics\StudentScoreTrendChartWidget;
use App\Filament\Widgets\Analytics\StudentSubjectPerformanceChartWidget;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class ViewUser extends ViewRecord
{
    use DisplaysRecordAnalytics;

    protected static string $resource = UserResource::class;

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
        $record = $this->getRecord();

        if (! $record instanceof User) {
            return [];
        }

        return match ($record->user_type) {
            'student' => ['studentId' => (int) $record->getKey()],
            'teacher' => ['teacherId' => (int) $record->getKey()],
            'institution' => $record->institution_id
                ? ['institutionId' => (int) $record->institution_id]
                : [],
            default => [],
        };
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getRecordAnalyticsWidgets(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof User) {
            return [];
        }

        return match ($record->user_type) {
            'student' => $this->studentWidgets($record),
            'teacher' => TeacherAnalyticsWidgets::forTeacher((int) $record->getKey()),
            'institution' => $record->institution_id
                ? InstitutionAnalyticsWidgets::forInstitution((int) $record->institution_id)
                : [],
            default => [],
        };
    }

    /**
     * @return list<class-string<Widget>>
     */
    private function studentWidgets(User $record): array
    {
        $widgets = [StudentOverviewStatsWidget::class];

        $overview = app(DashboardAnalyticsService::class)
            ->studentAnalyticsById((int) $record->getKey())['overview'] ?? [];

        if (($overview['total_completed_attempts'] ?? 0) > 0) {
            $widgets[] = StudentScoreTrendChartWidget::class;
            $widgets[] = StudentSubjectPerformanceChartWidget::class;
            $widgets[] = StudentCompetencyDistributionChartWidget::class;
        }

        return $widgets;
    }
}
