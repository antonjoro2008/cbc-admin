<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GuardianPerformanceReport;
use App\Models\User;
use App\Services\PerformanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TeacherShareController extends Controller
{
    public function __construct(
        private readonly PerformanceReportService $reports,
    ) {}

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

        $year = $request->year ?? (int) now()->year;
        $report = $this->reports->buildForStudent($student, $year);

        Mail::to($to)->send(new GuardianPerformanceReport($student, $report));

        return response()->json([
            'success' => true,
            'message' => 'Report sent to parent/guardian.',
        ]);
    }

}

