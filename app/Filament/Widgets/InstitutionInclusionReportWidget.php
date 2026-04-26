<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\InstitutionLearnerAnalyticsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class InstitutionInclusionReportWidget extends Widget
{
    protected static ?int $sort = -30;

    protected string $view = 'filament.widgets.institution-inclusion-report';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isInstitution() && (bool) $user->institution_id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $service = app(InstitutionLearnerAnalyticsService::class);
        $cohort = $service->learnerCohortAnalytics((int) $user->institution_id, null, 800);
        $inclusion = $cohort['inclusion_metrics'];

        $cohortRows = [];
        foreach ($inclusion['cohort_by_gender'] ?? [] as $key => $count) {
            $cohortRows[] = [
                'label' => InstitutionLearnerAnalyticsService::genderLabel($key),
                'key' => $key,
                'count' => (int) $count,
            ];
        }

        $performanceRows = [];
        foreach ($inclusion['performance_by_gender'] ?? [] as $key => $row) {
            $performanceRows[] = [
                'label' => InstitutionLearnerAnalyticsService::genderLabel($key),
                'average_percent' => $row['average_percent'] ?? 0,
                'attempts' => $row['assessment_attempts'] ?? 0,
                'learners' => $row['distinct_learners'] ?? 0,
            ];
        }

        return [
            'notes' => $inclusion['notes'] ?? [],
            'gender_reporting' => $inclusion['gender_reporting'] ?? [],
            'cohort_rows' => $cohortRows,
            'performance_rows' => $performanceRows,
        ];
    }
}
