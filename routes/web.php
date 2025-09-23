<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Add a simple login route to prevent "Route [login] not defined" errors
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');