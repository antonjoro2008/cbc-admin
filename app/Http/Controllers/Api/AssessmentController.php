<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Subject;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Feedback;
use App\Models\FeedbackMedia;
use App\Models\AnswerMedia;
use App\Models\Setting;
use App\Models\TokenUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssessmentController extends Controller
{
    /**
     * Get list of all assessments that have questions (for practice)
     */
    public function index(Request $request)
    {
        $query = Assessment::with(['subject', 'creator.institution'])
            ->whereHas('questions') // Only return assessments that have questions
            ->where('status', 1); // Only return assessments with status 1

        // Apply search filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paper_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('exam_body')) {
            $query->where('exam_body', $request->exam_body);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $assessments = $query->paginate($request->get('per_page', 20));

        // Get minutes per token setting
        $minutesPerToken = Setting::getValue('minutes_per_token', 1.0);

        // Add token cost to each assessment (1 token per assessment for now, but minutes will be deducted per minute)
        $assessments->getCollection()->transform(function ($assessment) use ($minutesPerToken) {
            $assessment->token_cost = 1; // Initial token cost to start assessment
            $assessment->minutes_per_token = $minutesPerToken;
            return $assessment;
        });

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }

    /**
     * Get assessment details
     */
    public function show(Request $request, Assessment $assessment)
    {
        // Check if assessment has questions
        if (!$assessment->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found or has no questions'
            ], 404);
        }

        $assessment->load([
            'subject',
            'creator.institution'
        ]);

        // Get sections with their questions for this assessment
        $sections = $assessment->getSectionsWithQuestions();
        $assessment->setRelation('sections', $sections);

        return response()->json([
            'success' => true,
            'data' => $assessment
        ]);
    }

    /**
     * Get assessments that a student has attempted
     */
    public function myAssessments(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = Assessment::with(['subject', 'creator.institution'])
            ->whereHas('attempts', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            })
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paper_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('exam_body')) {
            $query->where('exam_body', $request->exam_body);
        }

        $assessments = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }

    /**
     * Get assessments by subject ID
     */
    public function getBySubject(Request $request, $subjectId)
    {
        // Validate subject exists
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found'
            ], 404);
        }

        $query = Assessment::with(['subject', 'creator.institution'])
            ->where('subject_id', $subjectId)
            ->whereHas('questions') // Only return assessments that have questions
            ->where('status', 1); // Only return assessments with status 1

        // Apply search filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paper_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('exam_body')) {
            $query->where('exam_body', $request->exam_body);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $assessments = $query->paginate($request->get('per_page', 20));

        // Get minutes per token setting
        $minutesPerToken = Setting::getValue('minutes_per_token', 1.0);

        // Add token cost to each assessment (1 token per assessment for now, but minutes will be deducted per minute)
        $assessments->getCollection()->transform(function ($assessment) use ($minutesPerToken) {
            $assessment->token_cost = 1; // Initial token cost to start assessment
            $assessment->minutes_per_token = $minutesPerToken;
            return $assessment;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => $subject,
                'assessments' => $assessments
            ]
        ]);
    }

    /**
     * Get assessments for an institution (institution users only)
     */
    public function institutionAssessments(Request $request)
    {
        $user = $request->user();

        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = Assessment::whereHas('creator', function ($q) use ($user) {
            $q->where('institution_id', $user->institution_id);
        })
            ->with(['subject', 'creator', 'questions'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('year', '>=', now()->year - 1);
            } elseif ($request->status === 'archived') {
                $query->where('year', '<', now()->year - 1);
            }
        }

        $assessments = $query->paginate($request->get('per_page', 20));

        // Add sections to each assessment
        $assessments->getCollection()->transform(function ($assessment) {
            $sections = $assessment->getSectionsWithQuestions();
            $assessment->setRelation('sections', $sections);
            return $assessment;
        });

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }

    /**
     * Start an assessment (deduct tokens and create attempt record)
     */
    public function startAssessment(Request $request, Assessment $assessment)
    {
        $user = $request->user();

        // Check if user is a student
        if (!$user->isStudent() && !$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Only students or parents accounts can start assessments'
            ], 403);
        }

        // Check if assessment has questions
        if (!$assessment->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found or has no questions'
            ], 404);
        }

        // Get maximum number of attempts allowed
        $maxAttempts = Setting::getValue('max_number_of_assessment_attempts', 3);

        // Check existing attempts for this student and assessment
        $existingAttempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('student_id', $user->id)
            ->orderBy('attempt_number', 'desc')
            ->get();

        $totalAttempts = $existingAttempts->count();
        $inProgressAttempt = $existingAttempts->whereNull('completed_at')->first();

        // Check if user has reached maximum attempts
        if ($totalAttempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => "You have reached the maximum number of attempts ({$maxAttempts}) for this assessment",
                'data' => [
                    'max_attempts' => $maxAttempts,
                    'current_attempts' => $totalAttempts,
                    'status' => 'max_attempts_reached'
                ]
            ], 409);
        }

        // Check if there's an attempt in progress
        if ($inProgressAttempt) {
            return response()->json([
                'success' => true,
                'message' => 'Assessment is already in progress',
                'data' => [
                    'attempt_id' => $inProgressAttempt->id,
                    'attempt_number' => $inProgressAttempt->attempt_number,
                    'started_at' => $inProgressAttempt->started_at,
                    'completed_at' => $inProgressAttempt->completed_at,
                    'score' => $inProgressAttempt->score,
                    'status' => 'in_progress',
                    'tokens_deducted' => 0, // No new tokens deducted
                    'remaining_balance' => $user->getEffectiveWallet()->balance ?? 0
                ]
            ]);
        }

        try {
            DB::beginTransaction();

            // Get minutes per token setting
            $minutesPerToken = Setting::getValue('minutes_per_token', 1.0);

            // Get user's effective wallet (institution admin's wallet for institution students)
            $wallet = $user->getEffectiveWallet();
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please contact support.'
                ], 500);
            }

            // For starting assessment, we deduct 1 token and 1 minute (initial cost)
            $initialTokenCost = 1.0;
            $initialMinuteCost = 1.0;

            // Check if user has sufficient balance
            if (!$wallet->hasSufficientBalance($initialTokenCost) || !$wallet->hasSufficientMinutes($initialMinuteCost)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. You need ' . $initialTokenCost . ' tokens and ' . $initialMinuteCost . ' minutes to start this assessment.',
                    'data' => [
                        'required_tokens' => $initialTokenCost,
                        'required_minutes' => $initialMinuteCost,
                        'current_token_balance' => $wallet->balance,
                        'current_minutes_balance' => $wallet->available_minutes
                    ]
                ], 400);
            }

            // Deduct initial tokens and minutes from wallet
            $deducted = $wallet->deductTokensAndMinutes($initialTokenCost, $initialMinuteCost);
            if (!$deducted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deduct balance. Please try again.'
                ], 500);
            }

            // Calculate next attempt number
            $nextAttemptNumber = $totalAttempts + 1;

            // Create assessment attempt
            $attempt = AssessmentAttempt::create([
                'assessment_id' => $assessment->id,
                'student_id' => $user->id,
                'attempt_number' => $nextAttemptNumber,
                'started_at' => now(),
                'completed_at' => null,
                'score' => null
            ]);

            // Record initial token usage
            TokenUsage::create([
                'attempt_id' => $attempt->id,
                'tokens_used' => $initialTokenCost
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assessment started successfully',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'started_at' => $attempt->started_at,
                    'completed_at' => $attempt->completed_at,
                    'score' => $attempt->score,
                    'status' => 'in_progress',
                    'tokens_deducted' => $initialTokenCost,
                    'minutes_deducted' => $initialMinuteCost,
                    'remaining_token_balance' => $wallet->fresh()->balance,
                    'remaining_minutes_balance' => $wallet->fresh()->available_minutes
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to start assessment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit assessment answers
     */
    public function submitAssessment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assessment_id' => 'required|exists:assessments,id',
            'user_id' => 'required|exists:users,id',
            'submission_data' => 'required|array',
            'submission_data.start_time' => 'required|date',
            'submission_data.end_time' => 'required|date',
            'submission_data.time_taken_seconds' => 'required|integer|min:0',
            'submission_data.total_questions' => 'required|integer|min:1',
            'submission_data.questions_answered' => 'required|integer|min:0',
            'submission_data.answers' => 'required|array',
            'submission_data.answers.*.question_id' => 'required|exists:questions,id',
            'submission_data.answers.*.question_type' => 'required|in:mcq,true_false,short_answer,essay,matching,fill_blank',
            'submission_data.answers.*.answer' => 'required|array',
            'metadata' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $assessmentId = $request->assessment_id;
            $userId = $request->user_id;
            $submissionData = $request->submission_data;
            $answers = $submissionData['answers'];

            // Find the in-progress attempt for this student and assessment
            $attempt = AssessmentAttempt::where('assessment_id', $assessmentId)
                ->where('student_id', $userId)
                ->whereNull('completed_at')
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active attempt found for this assessment'
                ], 404);
            }

            // Update the attempt with completion data
            $attempt->update([
                'completed_at' => $submissionData['end_time'],
                'score' => 0 // Will be calculated later
            ]);

            $totalQuestions = $submissionData['total_questions'];
            $questionsAnswered = $submissionData['questions_answered'];
            $autoMarkedQuestions = 0;
            $notAutoMarkedQuestions = 0;
            $correctAnswers = 0;
            $totalMarksForAutoMarked = 0;
            $marksAwarded = 0;
            $feedbackData = [];

            // Process each answer
            foreach ($answers as $answerData) {
                $questionId = $answerData['question_id'];
                $questionType = $answerData['question_type'];
                $answer = $answerData['answer'];

                // Get question details
                $question = Question::with(['answers.media'])->find($questionId);
                if (!$question) {
                    continue;
                }

                $questionMarks = $question->marks;

                // Check if question can be auto-marked
                $canAutoMark = in_array($questionType, ['mcq', 'true_false', 'matching', 'fill_blank']);

                if ($canAutoMark) {
                    $autoMarkedQuestions++;
                    $totalMarksForAutoMarked += $questionMarks;
                    $isCorrect = $this->markAnswer($question, $questionType, $answer);
                    $marksForQuestion = $isCorrect ? $questionMarks : 0;
                    $marksAwarded += $marksForQuestion;

                    if ($isCorrect) {
                        $correctAnswers++;
                    }
                } else {
                    $notAutoMarkedQuestions++;
                    $isCorrect = null; // Manual marking required
                    $marksForQuestion = 0; // Will be marked manually later
                }

                // Save attempt answer
                $attemptAnswer = AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                    'selected_answer_id' => $answer['selected_answer_id'] ?? null,
                    'student_answer_text' => $this->formatStudentAnswer($questionType, $answer),
                    'is_correct' => $isCorrect,
                    'marks_awarded' => $marksForQuestion
                ]);

                // Generate feedback for auto-marked questions
                if ($canAutoMark) {
                    $feedback = $this->generateFeedback($question, $questionType, $answer, $isCorrect);

                    // Save feedback
                    $feedbackRecord = Feedback::create([
                        'attempt_answer_id' => $attemptAnswer->id,
                        'feedback_text' => $feedback['text'],
                        'ai_generated' => false
                    ]);

                    // Save feedback media if any
                    if (isset($feedback['media']) && !empty($feedback['media'])) {
                        foreach ($feedback['media'] as $media) {
                            FeedbackMedia::create([
                                'feedback_id' => $feedbackRecord->id,
                                'media_type' => $media['media_type'],
                                'media_url' => $media['file_path']
                            ]);
                        }
                    }

                    $feedbackData[] = [
                        'question_number' => $question->question_number,
                        'question_text' => $question->question_text,
                        'selected_answer' => $this->formatStudentAnswer($questionType, $answer),
                        'is_correct' => $isCorrect,
                        'explanation' => $feedback['all_explanations'],
                        'correct_answer' => $isCorrect ? null : $feedback['correct_answer'],
                        'media' => $feedback['media'] ?? []
                    ];
                }
            }

            // Calculate percentage based on auto-marked questions only
            $percentage = $totalMarksForAutoMarked > 0 ? round(($marksAwarded / $totalMarksForAutoMarked) * 100, 2) : 0;

            // Update attempt with final score
            $attempt->update(['score' => $marksAwarded]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assessment submitted successfully',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'summary' => [
                        'total_questions' => $totalQuestions,
                        'questions_answered' => $questionsAnswered,
                        'auto_marked_questions' => $autoMarkedQuestions,
                        'not_auto_marked_questions' => $notAutoMarkedQuestions,
                        'correct_answers' => $correctAnswers,
                        'incorrect_answers' => $autoMarkedQuestions - $correctAnswers,
                        'score' => $marksAwarded,
                        'out_of' => $totalMarksForAutoMarked,
                        'percentage' => $percentage
                    ],
                    'feedback' => $feedbackData
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Assessment submission failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark answer based on question type
     */
    private function markAnswer($question, $questionType, $answer)
    {
        switch ($questionType) {
            case 'mcq':
                $selectedAnswerId = $answer['selected_answer_id'] ?? null;
                $correctAnswer = $question->answers()->where('is_correct', true)->first();
                return $correctAnswer && $correctAnswer->id == $selectedAnswerId;

            case 'true_false':
                $selectedOption = $answer['selected_option'] ?? null;
                $correctAnswer = $question->answers()->where('is_correct', true)->first();
                return $correctAnswer && $correctAnswer->answer_text === ucfirst($selectedOption);

            case 'matching':
                // For matching questions, compare the matches
                $studentMatches = $answer['matches'] ?? [];
                $correctAnswers = $question->answers()->where('is_correct', true)->get();

                if (count($studentMatches) !== $correctAnswers->count()) {
                    return false;
                }

                foreach ($studentMatches as $match) {
                    $found = $correctAnswers->where('answer_text', $match['left_item'] . '|' . $match['right_item'])->first();
                    if (!$found) {
                        return false;
                    }
                }
                return true;

            case 'fill_blank':
                $studentBlanks = $answer['blanks'] ?? [];
                $correctAnswers = $question->answers()->where('is_correct', true)->get();

                if (count($studentBlanks) !== $correctAnswers->count()) {
                    return false;
                }

                foreach ($studentBlanks as $blank) {
                    $found = $correctAnswers->where('answer_text', $blank['text'])->first();
                    if (!$found) {
                        return false;
                    }
                }
                return true;

            default:
                return false;
        }
    }

    /**
     * Format student answer for storage
     */
    private function formatStudentAnswer($questionType, $answer)
    {
        switch ($questionType) {
            case 'mcq':
                return $answer['answer_text'] ?? '';
            case 'true_false':
                return $answer['answer_text'] ?? '';
            case 'short_answer':
            case 'essay':
                return $answer['text_response'] ?? '';
            case 'matching':
                return json_encode($answer['matches'] ?? []);
            case 'fill_blank':
                return json_encode($answer['blanks'] ?? []);
            default:
                return '';
        }
    }

    /**
     * Generate feedback for an answer
     */
    private function generateFeedback($question, $questionType, $answer, $isCorrect)
    {
        $feedback = [
            'text' => '',
            'explanation' => '',
            'correct_answer' => null,
            'all_explanations' => '',
            'media' => []
        ];

        // Get all answers for this question
        $allAnswers = $question->answers()->orderBy('id')->get();

        // Generate explanations for all possible answers
        $allExplanations = '';
        foreach ($allAnswers as $answerOption) {
            if ($answerOption->explanation) {
                // $optionLabel = $this->getAnswerOptionLabel($answerOption, $allAnswers);
                $allExplanations .= "<p>{$answerOption->answer_text} - {$answerOption->explanation}</p>";
            }
        }

        $feedback['all_explanations'] = $allExplanations;

        if ($isCorrect) {
            $feedback['text'] = 'Correct!';
            $correctAnswer = $question->answers()->where('is_correct', true)->first();
            if ($correctAnswer && $correctAnswer->explanation) {
                $feedback['explanation'] = $correctAnswer->explanation;
                $feedback['text'] .= ' ' . $correctAnswer->explanation;
            }
        } else {
            $feedback['text'] = 'Incorrect.';
            $correctAnswer = $question->answers()->where('is_correct', true)->first();
            if ($correctAnswer) {
                $feedback['correct_answer'] = $correctAnswer->answer_text;
                $feedback['explanation'] = $correctAnswer->explanation ?? 'This is the correct answer.';
                $feedback['text'] .= ' The correct answer is: ' . $correctAnswer->answer_text;
                if ($correctAnswer->explanation) {
                    $feedback['text'] .= '. ' . $correctAnswer->explanation;
                }

                // Include media from correct answer
                if ($correctAnswer->media) {
                    foreach ($correctAnswer->media as $media) {
                        $feedback['media'][] = [
                            'media_type' => $media->media_type,
                            'file_path' => $media->file_path,
                            'caption' => $media->caption
                        ];
                    }
                }
            }
        }

        return $feedback;
    }

    /**
     * Get answer option label (A, B, C, D, etc.)
     */
    private function getAnswerOptionLabel($answer, $allAnswers)
    {
        $index = $allAnswers->search(function ($item) use ($answer) {
            return $item->id === $answer->id;
        });

        return chr(65 + $index); // A, B, C, D, etc.
    }

    /**
     * Track assessment progress minute by minute
     */
    public function trackProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attempt_id' => 'required|exists:assessment_attempts,id',
            'minutes_elapsed' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $attemptId = $request->attempt_id;
            $minutesElapsed = $request->minutes_elapsed;

            // Find the assessment attempt
            $attempt = AssessmentAttempt::where('id', $attemptId)
                ->where('student_id', $user->id)
                ->whereNull('completed_at') // Only allow tracking for in-progress attempts
                ->first();

            if (!$attempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment attempt not found or not in progress'
                ], 404);
            }

            // Get user's effective wallet (institution admin's wallet for institution students)
            $wallet = $user->getEffectiveWallet();
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please contact support.'
                ], 500);
            }

            // Get minutes per token setting
            $minutesPerToken = Setting::getValue('minutes_per_token', 1.0);

            // Calculate tokens to deduct (fraction based on minutes elapsed)
            $tokensToDeduct = $minutesElapsed / $minutesPerToken;
            $minutesToDeduct = $minutesElapsed;

            // Check if user has sufficient balance
            if (!$wallet->hasSufficientBalance($tokensToDeduct) || !$wallet->hasSufficientMinutes($minutesToDeduct)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Please top up your account.',
                    'data' => [
                        'required_tokens' => $tokensToDeduct,
                        'required_minutes' => $minutesToDeduct,
                        'current_token_balance' => $wallet->balance,
                        'current_minutes_balance' => $wallet->available_minutes
                    ]
                ], 400);
            }

            DB::beginTransaction();

            // Deduct tokens and minutes from wallet
            $deducted = $wallet->deductTokensAndMinutes($tokensToDeduct, $minutesToDeduct);
            if (!$deducted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to deduct balance. Please try again.'
                ], 500);
            }

            // Record token usage for this minute
            TokenUsage::create([
                'attempt_id' => $attempt->id,
                'tokens_used' => $tokensToDeduct
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Progress tracked successfully',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'minutes_elapsed' => $minutesElapsed,
                    'tokens_deducted' => $tokensToDeduct,
                    'minutes_deducted' => $minutesToDeduct,
                    'remaining_token_balance' => $wallet->fresh()->balance,
                    'remaining_minutes_balance' => $wallet->fresh()->available_minutes
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to track progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}