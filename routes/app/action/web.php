<?php

use App\Http\Controllers\Action\TableActionController;
use App\Http\Controllers\Action\ViewActioController;
use App\Http\Controllers\Action\ViewCreateActionController;

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
    Route::get('/action/create', [ViewCreateActionController::class, 'create'])->name('action.create');

    // post
    Route::post('/action/table', [TableActionController::class, 'table'])->name('action.table');

});
