<?php

use App\Http\Controllers\Action\TableActionController;
use App\Http\Controllers\Action\ViewActioController;

/*
|--------------------------------------------------------------------------
| routes for pac-check-engine module design
|--------------------------------------------------------------------------
| Protected by: auth
*/

// Routes configured for application
Route::middleware(['auth'])->group(function () {
    // get
    Route::get('/action', [ViewActioController::class, 'view'])->name('action');

    // post
    Route::post('/action/table', [TableActionController::class, 'table'])->name('action.table');
});
