<?php

use App\Http\Controllers\Auth\AuthLoginController;
use App\Http\Controllers\Auth\ViewLoginController;

/*
|--------------------------------------------------------------------------
| routes for pac-check-engine module design
|--------------------------------------------------------------------------
| Protected by: *
*/

// get
Route::get('/login', [ViewLoginController::class, 'viewLogin'])->name('login');

// post
Route::post('/auth/login', [AuthLoginController::class, 'authLogin'])->name('auth.login');
