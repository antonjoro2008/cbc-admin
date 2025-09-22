<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ParentLearnerController;
use App\Http\Controllers\Api\InstitutionStudentController;
use App\Http\Controllers\SmsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/institution/register', [AuthController::class, 'registerInstitution']);

// Password reset routes (no authentication required)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Payment webhook routes (no authentication required)
Route::post('/payments/mpesa', [PaymentController::class, 'updateStatus']);

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // User management
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/password', [UserController::class, 'updatePassword']);
    
    // Dashboard & user-specific information
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/token-balance', [DashboardController::class, 'tokenBalance']);
    Route::get('/assessment-stats', [DashboardController::class, 'assessmentStats']);
    Route::get('/recent-assessments', [DashboardController::class, 'recentAssessments']);
    
    // Subjects
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
    
    // Assessments
    Route::get('/assessments', [AssessmentController::class, 'index']);
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show']);
    Route::get('/subjects/{subjectId}/assessments', [AssessmentController::class, 'getBySubject']);
    Route::post('/assessments/{assessment}/start', [AssessmentController::class, 'startAssessment']);
    Route::post('/assessments/submit', [AssessmentController::class, 'submitAssessment']);
    Route::post('/assessments/track-progress', [AssessmentController::class, 'trackProgress']);
    Route::get('/my-assessments', [AssessmentController::class, 'myAssessments']);
    
    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    
    // Token history
    Route::get('/token-history', [DashboardController::class, 'tokenHistory']);
    
    // Transactions
    Route::get('/my-transactions', [TransactionController::class, 'myTransactions']);
    
    // Parent learners routes (only for parent users)
    Route::middleware('parent')->group(function () {
        Route::get('/parent/learners', [ParentLearnerController::class, 'index']);
        Route::post('/parent/learners', [ParentLearnerController::class, 'store']);
        Route::post('/parent/learners/multiple', [ParentLearnerController::class, 'storeMultiple']);
        Route::get('/parent/learners/{parentLearner}', [ParentLearnerController::class, 'show']);
        Route::put('/parent/learners/{parentLearner}', [ParentLearnerController::class, 'update']);
        Route::delete('/parent/learners/{parentLearner}', [ParentLearnerController::class, 'destroy']);
    });
    
    // Institution-specific routes (only for institution users)
    Route::middleware('institution')->group(function () {
        Route::get('/institution/students', [InstitutionStudentController::class, 'index']);
        Route::post('/institution/students', [InstitutionStudentController::class, 'store']);
        Route::post('/institution/students/multiple', [InstitutionStudentController::class, 'storeMultiple']);
        Route::post('/institution/students/import', [InstitutionStudentController::class, 'importFromExcel']);
        Route::get('/institution/students/{student}', [InstitutionStudentController::class, 'show']);
        Route::put('/institution/students/{student}', [InstitutionStudentController::class, 'update']);
        Route::delete('/institution/students/{student}', [InstitutionStudentController::class, 'destroy']);
        
        Route::get('/institution/assessments', [AssessmentController::class, 'institutionAssessments']);
        Route::get('/institution/transactions', [TransactionController::class, 'institutionTransactions']);
    });
    
    // Admin routes (only for admin users)
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'adminUsers']);
        Route::get('/admin/institutions', [UserController::class, 'adminInstitutions']);
    });
    
    // SMS routes
    Route::prefix('sms')->group(function () {
        Route::post('/test', [SmsController::class, 'sendTestSms']);
        Route::post('/send', [SmsController::class, 'sendSmsToUser']);
        Route::post('/bulk', [SmsController::class, 'sendBulkSms']);
        Route::get('/test-config', [SmsController::class, 'testConfig']);
    });
});
