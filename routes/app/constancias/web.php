<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Constancias\ViewConstanciasController;
use App\Http\Controllers\Constancias\TableConstanciasController;
use App\Http\Controllers\Constancias\DataConstanciasController;
use App\Http\Controllers\Constancias\UpdateEstatusConstanciasController;

Route::middleware(['auth', 'role:admin_oc,supervisor_oc,revisor_est'])->group(function () {

    Route::get('/constancias', [ViewConstanciasController::class, 'view'])
        ->name('constancias');

    Route::post('/constancias/table', [TableConstanciasController::class, 'table'])
        ->name('constancias.table');

    Route::post('/constancias/data', [DataConstanciasController::class, 'data'])
        ->name('constancias.data');

    Route::post('/constancias/estatus', [UpdateEstatusConstanciasController::class, 'update'])
        ->name('constancias.estatus');
});