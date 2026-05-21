<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Feedback;
use App\Models\ParentLearner;
use App\Models\Question;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AssessmentAttemptSummaryService
{
    public function __construct(
        private readonly ParentDashboardService $parentDashboard,
        private readonly InstitutionLearnerAnalyticsService $cohortAnalytics,
    ) {}

    /**
     * @return array<string, int>
     */
    public function assessmentTotalMarksMap(array $assessmentIds): array
    {
        if ($assessmentIds === []) {
            return [];
        }

        return DB::table('questions')
            ->whereIn('assessment_id', $assessmentIds)
            ->selectRaw('assessment_id, COALESCE(SUM(marks), 0) as total_marks')
            ->groupBy('assessment_id')
            ->pluck('total_marks', 'assessment_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public function attemptPercent(AssessmentAttempt $attempt, ?array $marksMap = null): ?float
    {
        if ($attempt->score === null) {
            return null;
        }

        $assessmentId = (int) $attempt->assessment_id;
        $outOf = $marksMap[$assessmentId] ?? null;

        if ($outOf === null) {
            $map = $this->assessmentTotalMarksMap([$assessmentId]);
            $outOf = $map[$assessmentId] ?? 0;
        }

        if ($outOf <= 0) {
            return null;
        }

        return round(((float) $attempt->score / (float) $outOf) * 100, 2);
    }

    public function canViewAttempt(User $viewer, AssessmentAttempt $attempt): bool
    {
        $student = $attempt->student;
        if (! $student || ! $student->isStudent()) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        if ($viewer->isStudent() && (int) $viewer->id === (int) $student->id) {
            return true;
        }

        if ($viewer->isParent()) {
            return $this->parentDashboard->parentCanViewStudent($viewer, $student);
        }

        if ($viewer->institution_id && (int) $student->institution_id === (int) $viewer->institution_id) {
            if ($viewer->isInstitution()) {
                return true;
            }

            if ($viewer->isTeacher() && (int) $viewer->classroom_id === (int) $student->classroom_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<int>
     */
    public function studentIdsVisibleTo(User $viewer, ?int $studentId = null): array
    {
        if ($studentId !== null) {
            $student = User::where('id', $studentId)->where('user_type', 'student')->first();
            if ($student && $this->viewerCanAccessStudent($viewer, $student)) {
                return [(int) $studentId];
            }

            return [];
        }

        if ($viewer->isStudent()) {
            return [(int) $viewer->id];
        }

        if ($viewer->isParent()) {
            $ids = [];
            foreach ($viewer->parentLearners as $learner) {
                $student = $this->parentDashboard->resolveStudentAccount($viewer, $learner);
                if ($student) {
                    $ids[] = (int) $student->id;
                }
            }

            return array_values(array_unique($ids));
        }

        if ($viewer->isTeacher() && $viewer->institution_id && $viewer->classroom_id) {
            return User::query()
                ->where('user_type', 'student')
                ->where('institution_id', $viewer->institution_id)
                ->where('classroom_id', $viewer->classroom_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($viewer->isInstitution() && $viewer->institution_id) {
            return User::query()
                ->where('user_type', 'student')
                ->where('institution_id', $viewer->institution_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    public function viewerCanAccessStudent(User $viewer, User $student): bool
    {
        if (! $student->isStudent()) {
            return false;
        }

        if ($viewer->isAdmin()) {
            return true;
        }

        if ($viewer->isStudent() && (int) $viewer->id === (int) $student->id) {
            return true;
        }

        if ($viewer->isParent()) {
            return $this->parentDashboard->parentCanViewStudent($viewer, $student);
        }

        if ($viewer->institution_id && (int) $student->institution_id === (int) $viewer->institution_id) {
            if ($viewer->isInstitution()) {
                return true;
            }

            if ($viewer->isTeacher() && (int) $viewer->classroom_id === (int) $student->classroom_id) {
                return true;
            }
        }

        return false;
    }

    public function listAttempts(User $viewer, ?int $studentId = null, int $perPage = 20): LengthAwarePaginator
    {
        $studentIds = $this->studentIdsVisibleTo($viewer, $studentId);

        $query = AssessmentAttempt::query()
            ->with(['assessment.subject', 'student:id,name,grade_level,admission_number'])
            ->whereIn('student_id', $studentIds === [] ? [-1] : $studentIds)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->orderByDesc('id');

        return $query->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatAttemptListItem(AssessmentAttempt $attempt, ?array $marksMap = null): array
    {
        $percent = $this->attemptPercent($attempt, $marksMap);
        $completed = $attempt->completed_at !== null;

        return [
            'attempt_id' => $attempt->id,
            'assessment_id' => $attempt->assessment_id,
            'assessment_title' => $attempt->assessment?->title ?? 'Assessment',
            'subject' => $attempt->assessment?->subject?->name,
            'student_id' => $attempt->student_id,
            'student_name' => $attempt->student?->name,
            'grade_level' => $attempt->student?->grade_level,
            'score_percent' => $percent,
            'competency_level' => $percent !== null
                ? $this->cohortAnalytics->competencyDescriptor($percent)
                : ($completed ? null : 'In progress'),
            'status' => $completed ? 'completed' : 'in_progress',
            'started_at' => $attempt->started_at?->toIso8601String(),
            'completed_at' => $attempt->completed_at?->toIso8601String(),
            'attempt_number' => $attempt->attempt_number,
        ];
    }

    /**
     * Rebuild summary payload for the assessment-summary UI (matches post-submit shape).
     *
     * @return array<string, mixed>
     */
    public function buildSummaryPayload(AssessmentAttempt $attempt): array
    {
        $attempt->load([
            'assessment.subject',
            'student:id,name,grade_level',
            'attemptAnswers.question.answers.media',
            'attemptAnswers.feedback.media',
        ]);

        $totalQuestions = 0;
        $questionsAnswered = $attempt->attemptAnswers->count();
        $autoMarkedQuestions = 0;
        $notAutoMarkedQuestions = 0;
        $correctAnswers = 0;
        $marksAwarded = 0;
        $totalMarksForAutoMarked = 0;
        $categoryScores = [];
        $categoryTotals = [];
        $feedbackData = [];

        foreach ($attempt->attemptAnswers as $attemptAnswer) {
            $question = $attemptAnswer->question;
            if (! $question) {
                continue;
            }

            $totalQuestions++;
            $questionMarks = (int) ($question->marks ?? 0);
            $questionType = $question->question_type ?? 'mcq';
            $canAutoMark = in_array($questionType, ['mcq', 'true_false', 'matching', 'fill_blank'], true);

            if ($canAutoMark) {
                $autoMarkedQuestions++;
                $totalMarksForAutoMarked += $questionMarks;
                $marksForQuestion = (int) ($attemptAnswer->marks_awarded ?? 0);
                $marksAwarded += $marksForQuestion;

                if ($attemptAnswer->is_correct) {
                    $correctAnswers++;
                }

                if ($question->category_tag) {
                    $tag = $question->category_tag;
                    $categoryScores[$tag] = ($categoryScores[$tag] ?? 0) + $marksForQuestion;
                    $categoryTotals[$tag] = ($categoryTotals[$tag] ?? 0) + $questionMarks;
                }

                $feedbackRecord = $attemptAnswer->feedback->first();
                $feedbackData[] = [
                    'question_number' => $question->question_number,
                    'question_text' => $question->question_text,
                    'category_tag' => $question->category_tag,
                    'selected_answer' => $attemptAnswer->student_answer_text,
                    'is_correct' => (bool) $attemptAnswer->is_correct,
                    'explanation' => $feedbackRecord?->feedback_text ?? '',
                    'correct_answer' => null,
                    'media' => $feedbackRecord
                        ? $feedbackRecord->media->map(fn ($m) => [
                            'media_type' => $m->media_type,
                            'file_path' => $m->media_url ?? $m->file_path ?? null,
                            'caption' => $m->caption ?? null,
                        ])->values()->all()
                        : [],
                ];
            } else {
                $notAutoMarkedQuestions++;
            }
        }

        $categoryScoresData = [];
        foreach ($categoryScores as $categoryTag => $score) {
            $totalForCategory = $categoryTotals[$categoryTag] ?? 0;
            $categoryScoresData[] = [
                'category_tag' => $categoryTag,
                'score' => $score,
                'out_of' => $totalForCategory,
                'percentage' => $totalForCategory > 0 ? round(($score / $totalForCategory) * 100, 2) : 0,
            ];
        }

        $percentFromAutoMarked = $totalMarksForAutoMarked > 0
            ? round(($marksAwarded / $totalMarksForAutoMarked) * 100, 2)
            : 0;
        $categoryTotalOut = array_sum($categoryTotals);
        $categoryTotalScore = array_sum($categoryScores);
        $percent = (count($categoryScores) >= 2 && $categoryTotalOut > 0)
            ? round(($categoryTotalScore / $categoryTotalOut) * 100, 2)
            : $percentFromAutoMarked;

        $timeTaken = 0;
        if ($attempt->started_at && $attempt->completed_at) {
            $timeTaken = max(0, $attempt->started_at->diffInSeconds($attempt->completed_at));
        }

        return [
            'attempt_id' => $attempt->id,
            'assessment_id' => $attempt->assessment_id,
            'assessment' => $attempt->assessment ? [
                'id' => $attempt->assessment->id,
                'title' => $attempt->assessment->title,
                'duration_minutes' => $attempt->assessment->duration_minutes ?? null,
                'subject' => $attempt->assessment->subject?->name,
            ] : null,
            'student' => $attempt->student ? [
                'id' => $attempt->student->id,
                'name' => $attempt->student->name,
                'grade_level' => $attempt->student->grade_level,
            ] : null,
            'summary' => [
                'total_questions' => max($totalQuestions, $questionsAnswered),
                'questions_answered' => $questionsAnswered,
                'auto_marked_questions' => $autoMarkedQuestions,
                'not_auto_marked_questions' => $notAutoMarkedQuestions,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => max(0, $autoMarkedQuestions - $correctAnswers),
                'score' => $marksAwarded,
                'out_of' => $totalMarksForAutoMarked,
                'percentage' => $percent,
            ],
            'category_scores' => $categoryScoresData,
            'feedback' => $feedbackData,
            'submission_data' => [
                'start_time' => $attempt->started_at?->toIso8601String(),
                'end_time' => $attempt->completed_at?->toIso8601String(),
                'time_taken_seconds' => $timeTaken,
                'total_questions' => max($totalQuestions, $questionsAnswered),
                'questions_answered' => $questionsAnswered,
            ],
        ];
    }
}
