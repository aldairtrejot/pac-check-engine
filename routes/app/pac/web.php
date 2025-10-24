<?php

use App\Http\Controllers\Pac\ViewPacController;

/*
|--------------------------------------------------------------------------
| routes for pac-check-engine module design
|--------------------------------------------------------------------------
| Protected by: auth
*/

// Routes configured for application
Route::middleware(['auth'])->group(function () {
    // get
    Route::get('/pac', [ViewPacController::class, 'viewPac'])->name('pac');

    // post
});
