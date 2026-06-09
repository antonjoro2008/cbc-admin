<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentBookEntry;
use App\Models\User;
use App\Services\PerformanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AssessmentBookController extends Controller
{
    public function __construct(
        private readonly PerformanceReportService $reports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $teacher = Auth::user();
        if (! $teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Teacher access required'], 403);
        }

        $query = AssessmentBookEntry::query()
            ->with(['student:id,name,grade_level,admission_number'])
            ->where('teacher_id', $teacher->id);

        if ($request->filled('student_id')) {
            $studentId = (int) $request->student_id;
            if (! $this->teacherCanAccessStudent($teacher, $studentId)) {
                return response()->json(['success' => false, 'message' => 'Learner not found'], 404);
            }
            $query->where('student_id', $studentId);
        }

        if ($request->filled('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        if ($request->filled('year')) {
            $query->whereYear('period_start', (int) $request->year);
        }

        $entries = $query
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (AssessmentBookEntry $entry) => $this->formatEntry($entry));

        return response()->json([
            'success' => true,
            'data' => ['entries' => $entries],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $teacher = Auth::user();
        if (! $teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Teacher access required'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'period_type' => 'required|in:daily,weekly,monthly,termly',
            'period_start' => 'required|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'learning_area' => 'required|string|max:120',
            'strand' => 'nullable|string|max:120',
            'activity_observed' => 'nullable|string|max:2000',
            'score_percent' => 'nullable|integer|min:0|max:100',
            'cbe_level' => 'nullable|in:BE,AE,ME,EE',
            'strengths' => 'nullable|string|max:2000',
            'gaps_to_address' => 'nullable|string|max:2000',
            'next_steps' => 'nullable|string|max:2000',
            'teacher_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $student = User::find((int) $request->student_id);
        if (! $student || ! $this->teacherCanAccessStudent($teacher, $student->id)) {
            return response()->json(['success' => false, 'message' => 'Learner not found'], 404);
        }

        $score = $request->filled('score_percent') ? (int) $request->score_percent : null;
        $cbeLevel = $request->cbe_level;
        if ($score !== null && ! $cbeLevel) {
            $cbeLevel = $this->reports->codeFromPercent((float) $score);
        }

        $entry = AssessmentBookEntry::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'institution_id' => $teacher->institution_id,
            'classroom_id' => $student->classroom_id ?? $teacher->classroom_id,
            'period_type' => $request->period_type,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'learning_area' => $request->learning_area,
            'strand' => $request->strand,
            'activity_observed' => $request->activity_observed,
            'score_percent' => $score,
            'cbe_level' => $cbeLevel,
            'strengths' => $request->strengths,
            'gaps_to_address' => $request->gaps_to_address,
            'next_steps' => $request->next_steps,
            'teacher_notes' => $request->teacher_notes,
        ]);

        $entry->load('student:id,name,grade_level,admission_number');

        return response()->json([
            'success' => true,
            'message' => 'Assessment book entry saved.',
            'data' => $this->formatEntry($entry),
        ], 201);
    }

    public function destroy(AssessmentBookEntry $entry): JsonResponse
    {
        $teacher = Auth::user();
        if (! $teacher->isTeacher() || (int) $entry->teacher_id !== (int) $teacher->id) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Entry removed.',
        ]);
    }

    public function performanceReport(Request $request, User $student): JsonResponse
    {
        $teacher = Auth::user();
        if (! $teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Teacher access required'], 403);
        }

        if (! $this->teacherCanAccessStudent($teacher, $student->id)) {
            return response()->json(['success' => false, 'message' => 'Learner not found'], 404);
        }

        $year = $request->filled('year') ? (int) $request->year : (int) now()->year;
        $report = $this->reports->buildForStudent($student, $year);

        return response()->json([
            'success' => true,
            'data' => [
                'learner' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade_level' => $student->grade_level,
                ],
                'report' => $report,
            ],
        ]);
    }

    private function teacherCanAccessStudent(User $teacher, int $studentId): bool
    {
        $student = User::query()
            ->where('id', $studentId)
            ->where('user_type', 'student')
            ->where('institution_id', $teacher->institution_id)
            ->first();

        if (! $student) {
            return false;
        }

        if ($teacher->classroom_id && (int) $student->classroom_id !== (int) $teacher->classroom_id) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEntry(AssessmentBookEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'student_id' => $entry->student_id,
            'student_name' => $entry->student?->name,
            'period_type' => $entry->period_type,
            'period_start' => $entry->period_start?->toDateString(),
            'period_end' => $entry->period_end?->toDateString(),
            'learning_area' => $entry->learning_area,
            'strand' => $entry->strand,
            'activity_observed' => $entry->activity_observed,
            'score_percent' => $entry->score_percent,
            'cbe_level' => $entry->cbe_level,
            'cbe_level_label' => $entry->cbe_level
                ? $this->reports->levelFromCode($entry->cbe_level)
                : null,
            'strengths' => $entry->strengths,
            'gaps_to_address' => $entry->gaps_to_address,
            'next_steps' => $entry->next_steps,
            'teacher_notes' => $entry->teacher_notes,
            'created_at' => $entry->created_at?->toIso8601String(),
        ];
    }
}
