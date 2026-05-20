<?php

namespace App\Filament\GlobalSearch;

use App\Filament\Resources\Institutions\InstitutionResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Tables\PlatformLearnersTable;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Illuminate\Database\Eloquent\Builder;

class CbcGlobalSearchProvider implements GlobalSearchProvider
{
    private const RESULT_LIMIT = 8;

    public function __construct(
        protected DashboardAnalyticsService $analytics,
    ) {}

    public function getResults(string $query): ?GlobalSearchResults
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        $builder = GlobalSearchResults::make();

        $learners = $this->searchLearners($query);
        if ($learners !== []) {
            $builder->category('Learners', $learners);
        }

        $institutions = $this->searchInstitutions($query);
        if ($institutions !== []) {
            $builder->category('Institutions', $institutions);
        }

        $subjects = $this->searchSubjects($query);
        if ($subjects !== []) {
            $builder->category('Subjects', $subjects);
        }

        if ($builder->getCategories()->isEmpty()) {
            return $builder;
        }

        return $builder;
    }

    /**
     * @return list<GlobalSearchResult>
     */
    private function searchLearners(string $query): array
    {
        if (! UserResource::canViewAny()) {
            return [];
        }

        $students = User::query()
            ->where('user_type', 'student')
            ->with(['institution:id,name', 'classroom:id,name'])
            ->where(function (Builder $builder) use ($query): void {
                $this->applySearchColumns(
                    $builder,
                    ['name', 'email', 'phone_number', 'admission_number'],
                    $query,
                );
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'name', 'grade_level', 'institution_id', 'classroom_id']);

        if ($students->isEmpty()) {
            return [];
        }

        $statsById = PlatformLearnersTable::baseQuery()
            ->whereIn('users.id', $students->pluck('id'))
            ->get()
            ->keyBy('id');

        $results = [];

        foreach ($students as $student) {
            $stats = $statsById->get($student->id);
            $details = [
                'Institution' => $student->institution?->name ?? '—',
            ];

            if (filled($student->grade_level)) {
                $details['Grade'] = (string) $student->grade_level;
            }

            if (filled($student->classroom?->name)) {
                $details['Class'] = (string) $student->classroom->name;
            }

            if ($stats?->average_percent !== null) {
                $details['Avg score'] = $this->formatPercent((float) $stats->average_percent);
            }

            if (filled($stats?->learner_status)) {
                $details['Status'] = (string) $stats->learner_status;
            }

            if ((int) ($stats?->completed_attempts ?? 0) > 0) {
                $details['Attempts'] = (string) $stats->completed_attempts;
            }

            $results[] = new GlobalSearchResult(
                title: $student->name,
                url: UserResource::getUrl('view', ['record' => $student]),
                details: $details,
            );
        }

        return $results;
    }

    /**
     * @return list<GlobalSearchResult>
     */
    private function searchInstitutions(string $query): array
    {
        if (! InstitutionResource::canViewAny()) {
            return [];
        }

        $institutions = Institution::query()
            ->where(function (Builder $builder) use ($query): void {
                $this->applySearchColumns(
                    $builder,
                    ['name', 'email', 'phone', 'motto'],
                    $query,
                );
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'name']);

        $results = [];

        foreach ($institutions as $institution) {
            $data = $this->analytics->institutionAnalyticsById($institution->id);
            $summary = $data['summary'] ?? [];

            $details = [
                'Learners' => (string) ($summary['learners'] ?? 0),
            ];

            if (isset($summary['average_school_score_percent'])) {
                $details['School avg'] = $this->formatPercent((float) $summary['average_school_score_percent']);
            }

            if (isset($summary['completion_rate_percent'])) {
                $details['Completion'] = $this->formatPercent((float) $summary['completion_rate_percent']);
            }

            if (isset($summary['distinct_learners_active_last_30_days'])) {
                $details['Active (30d)'] = (string) $summary['distinct_learners_active_last_30_days'];
            }

            $results[] = new GlobalSearchResult(
                title: $institution->name,
                url: InstitutionResource::getUrl('view', ['record' => $institution]),
                details: $details,
            );
        }

        return $results;
    }

    /**
     * @return list<GlobalSearchResult>
     */
    private function searchSubjects(string $query): array
    {
        if (! SubjectResource::canViewAny()) {
            return [];
        }

        $subjects = Subject::query()
            ->where(function (Builder $builder) use ($query): void {
                $this->applySearchColumns(
                    $builder,
                    ['name', 'code'],
                    $query,
                );
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get(['id', 'name', 'code']);

        $results = [];

        foreach ($subjects as $subject) {
            $data = $this->analytics->subjectAnalyticsById($subject->id);
            $overview = $data['overview'] ?? [];

            $details = [
                'Code' => $subject->code ?? '—',
                'Assessments' => (string) ($overview['total_assessments'] ?? 0),
            ];

            if (isset($overview['average_score_percent'])) {
                $details['Avg score'] = $this->formatPercent((float) $overview['average_score_percent']);
            }

            if (isset($overview['distinct_learners'])) {
                $details['Learners'] = (string) $overview['distinct_learners'];
            }

            if (isset($overview['completion_rate_percent'])) {
                $details['Completion'] = $this->formatPercent((float) $overview['completion_rate_percent']);
            }

            $results[] = new GlobalSearchResult(
                title: $subject->name,
                url: SubjectResource::getUrl('view', ['record' => $subject]),
                details: $details,
            );
        }

        return $results;
    }

    /**
     * @param  list<string>  $columns
     */
    private function applySearchColumns(Builder $builder, array $columns, string $query): void
    {
        $term = '%' . $query . '%';

        $builder->where(function (Builder $nested) use ($columns, $term): void {
            foreach ($columns as $column) {
                $nested->orWhere($column, 'like', $term);
            }
        });
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 1) . '%';
    }
}
