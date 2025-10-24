<?php

use App\Http\Controllers\Auth\Login\ViewLoginController;

/*
|--------------------------------------------------------------------------
| routes for student module design
|--------------------------------------------------------------------------
| Protected by: *
*/

// get
Route::get('/login', [ViewLoginController::class, 'viewLogin'])->name('login');
