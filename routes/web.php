<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Add a simple login route to prevent "Route [login] not defined" errors
Route::get('/login', function () {
    return response()->json([
        'message' => 'Please use the API login endpoint: POST /api/login'
    ], 401);
})->name('login');