<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AssessmentBookEntry;
use App\Models\User;
use Illuminate\Support\Collection;

class PerformanceReportService
{
    public function __construct(
        private readonly AssessmentAttemptSummaryService $attemptSummaries,
    ) {}

    /**
     * Build guardian performance report: assessment book (primary) + digital attempts (detail section).
     *
     * @return array<string, mixed>
     */
    public function buildForStudent(User $student, ?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        $entries = AssessmentBookEntry::query()
            ->where('student_id', $student->id)
            ->whereYear('period_start', $year)
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->get();

        $digitalAttempts = $this->digitalAttemptsSection($student, $year);

        if ($entries->isEmpty()) {
            return array_merge($this->emptyBookReport($student, $year), [
                'digital_attempts' => $digitalAttempts,
            ]);
        }

        $scored = $entries->whereNotNull('score_percent');
        $average = $scored->isNotEmpty()
            ? round((float) $scored->avg('score_percent'), 1)
            : null;

        $overallLevel = $average !== null
            ? $this->levelFromPercent($average)
            : $this->resolveOverallLevelFromEntries($entries);

        $subjects = $this->subjectRowsFromEntries($entries);
        $termly = $entries->where('period_type', 'termly')->sortByDesc('period_start')->first();

        return [
            'year' => $year,
            'grade' => $student->grade_level ?? '—',
            'average_percent' => $average !== null ? $average.'%' : '—',
            'overall_level' => $overallLevel,
            'mn_mks' => $average !== null ? $average.'%' : '—',
            'tt_mks' => (string) $scored->count().' recorded',
            'subjects' => $subjects,
            'source' => 'assessment_book',
            'entries_count' => $entries->count(),
            'teacher_summary' => $termly?->teacher_notes ?? $entries->first()?->teacher_notes,
            'periods_recorded' => $entries->pluck('period_type')->unique()->values()->all(),
            'digital_attempts' => $digitalAttempts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function digitalAttemptsSection(User $student, int $year): array
    {
        $attempts = AssessmentAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->whereYear('completed_at', $year)
            ->with(['assessment.subject'])
            ->orderByDesc('completed_at')
            ->limit(25)
            ->get();

        if ($attempts->isEmpty()) {
            return [
                'attempts' => [],
                'count' => 0,
                'average_percent' => '—',
                'overall_level' => '—',
            ];
        }

        $assessmentIds = $attempts->pluck('assessment_id')->unique()->filter()->all();
        $marksMap = $this->attemptSummaries->assessmentTotalMarksMap($assessmentIds);

        $items = [];
        $percents = [];

        foreach ($attempts as $attempt) {
            $percent = $this->attemptSummaries->attemptPercent($attempt, $marksMap);
            if ($percent !== null) {
                $percents[] = $percent;
            }

            $items[] = [
                'attempt_id' => $attempt->id,
                'assessment_title' => $attempt->assessment?->title ?? 'Assessment',
                'subject' => $attempt->assessment?->subject?->name,
                'completed_at' => $attempt->completed_at?->toDateString(),
                'score_percent' => $percent !== null ? round($percent, 1) : null,
                'level' => $percent !== null ? $this->levelFromPercent($percent) : '—',
            ];
        }

        $avg = $percents !== [] ? round(array_sum($percents) / count($percents), 1) : null;

        return [
            'attempts' => $items,
            'count' => count($items),
            'average_percent' => $avg !== null ? $avg.'%' : '—',
            'overall_level' => $avg !== null ? $this->levelFromPercent($avg) : '—',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBookReport(User $student, int $year): array
    {
        return [
            'year' => $year,
            'grade' => $student->grade_level ?? '—',
            'average_percent' => '—',
            'overall_level' => '—',
            'mn_mks' => '—',
            'tt_mks' => '0 recorded',
            'subjects' => [],
            'source' => 'assessment_book',
            'entries_count' => 0,
            'teacher_summary' => null,
            'periods_recorded' => [],
        ];
    }

    /**
     * @param  Collection<int, AssessmentBookEntry>  $entries
     * @return list<array<string, mixed>>
     */
    private function subjectRowsFromEntries(Collection $entries): array
    {
        return $entries
            ->groupBy(fn (AssessmentBookEntry $e) => strtolower(trim($e->learning_area)))
            ->map(function (Collection $group) {
                /** @var AssessmentBookEntry $latest */
                $latest = $group->sortByDesc('period_start')->first();
                $scored = $group->whereNotNull('score_percent');
                $avg = $scored->isNotEmpty()
                    ? round((float) $scored->avg('score_percent'), 1)
                    : null;
                $level = $avg !== null
                    ? $this->levelFromPercent($avg)
                    : ($latest->cbe_level ? $this->levelFromCode($latest->cbe_level) : '—');

                $strands = $group->pluck('strand')->filter()->unique()->values();

                return [
                    'code' => $latest->learning_area,
                    'strand' => $strands->isNotEmpty() ? $strands->implode(', ') : null,
                    'percent' => $avg !== null ? $avg.'%' : '—',
                    'level' => $level,
                    'notes' => $latest->next_steps ?? $latest->strengths,
                ];
            })
            ->values()
            ->sortBy('code')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AssessmentBookEntry>  $entries
     */
    private function resolveOverallLevelFromEntries(Collection $entries): string
    {
        $priority = ['termly', 'monthly', 'weekly', 'daily'];
        foreach ($priority as $type) {
            $match = $entries->where('period_type', $type)->sortByDesc('period_start')->first();
            if ($match?->cbe_level) {
                return $this->levelFromCode($match->cbe_level);
            }
        }

        return '—';
    }

    public function levelFromPercent(float $percent): string
    {
        if ($percent < 50) {
            return 'Below Expectation (BE)';
        }
        if ($percent <= 70) {
            return 'Approaching Expectation (AE)';
        }
        if ($percent <= 85) {
            return 'Meeting Expectation (ME)';
        }

        return 'Exceeding Expectation (EE)';
    }

    public function codeFromPercent(float $percent): string
    {
        if ($percent < 50) {
            return 'BE';
        }
        if ($percent <= 70) {
            return 'AE';
        }
        if ($percent <= 85) {
            return 'ME';
        }

        return 'EE';
    }

    public function levelFromCode(string $code): string
    {
        return match (strtoupper($code)) {
            'BE' => 'Below Expectation (BE)',
            'AE' => 'Approaching Expectation (AE)',
            'ME' => 'Meeting Expectation (ME)',
            'EE' => 'Exceeding Expectation (EE)',
            default => $code,
        };
    }
}
