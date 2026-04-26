<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GuardianPerformanceReport;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TeacherShareController extends Controller
{
    public function sendGuardianReport(Request $request, User $student): JsonResponse
    {
        $teacher = Auth::user();

        if (!$teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Teacher access required'], 403);
        }

        if ($student->user_type !== 'student' || $student->institution_id !== $teacher->institution_id) {
            return response()->json(['success' => false, 'message' => 'Learner not found'], 404);
        }

        // Teacher can only share for their classroom learners
        if ($teacher->classroom_id && $student->classroom_id !== $teacher->classroom_id) {
            return response()->json(['success' => false, 'message' => 'Access denied for this learner'], 403);
        }

        $validator = Validator::make($request->all(), [
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $to = $student->guardian_email;
        if (!$to) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian email not available for this learner.',
            ], 422);
        }

        // Build a lightweight report from recent attempts (best effort)
        $attempts = AssessmentAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->with('assessment')
            ->orderBy('completed_at', 'desc')
            ->limit(50)
            ->get();

        $avgPercent = 0;
        $n = 0;
        foreach ($attempts as $a) {
            $outOf = $a->assessment?->questions()->sum('marks') ?: null;
            if ($outOf && $a->score !== null) {
                $avgPercent += (float) (($a->score / $outOf) * 100);
                $n++;
            }
        }
        $avgPercent = $n ? round($avgPercent / $n, 2) : 0;

        $report = [
            'year' => $request->year ?? now()->year,
            'grade' => $student->grade_level ?? '-',
            'average_percent' => $avgPercent . '%',
            'overall_level' => $this->toLevel($avgPercent),
            'mn_mks' => $avgPercent . '%',
            'tt_mks' => $n ? '—' : '—',
            'subjects' => [], // Hook: can be enhanced by category/subject stats later
        ];

        Mail::to($to)->send(new GuardianPerformanceReport($student, $report));

        return response()->json([
            'success' => true,
            'message' => 'Report sent to parent/guardian.',
        ]);
    }

    private function toLevel(float $p): string
    {
        if ($p < 50) {
            return 'Below Expectation (BE)';
        }
        if ($p <= 70) {
            return 'Approaching Expectation (AE)';
        }
        if ($p <= 85) {
            return 'Meeting Expectation (ME)';
        }

        return 'Exceeding Expectation (EE)';
    }
}

