<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TransactionController;

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
    
    // Assessments
    Route::get('/assessments', [AssessmentController::class, 'index']);
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show']);
    Route::get('/my-assessments', [AssessmentController::class, 'myAssessments']);
    
    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    
    // Token history
    Route::get('/token-history', [DashboardController::class, 'tokenHistory']);
    
    // Transactions
    Route::get('/my-transactions', [TransactionController::class, 'myTransactions']);
    
    // Institution-specific routes (only for institution users)
    Route::middleware('institution')->group(function () {
        Route::get('/institution/students', [UserController::class, 'institutionStudents']);
        Route::get('/institution/assessments', [AssessmentController::class, 'institutionAssessments']);
        Route::get('/institution/transactions', [TransactionController::class, 'institutionTransactions']);
    });
    
    // Admin routes (only for admin users)
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'adminUsers']);
        Route::get('/admin/institutions', [UserController::class, 'adminInstitutions']);
    });
});
