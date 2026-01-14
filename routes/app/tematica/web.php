<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tematica\ViewTematicaController;
use App\Http\Controllers\Tematica\ViewCreateTematicaController;
use App\Http\Controllers\Tematica\ViewEditTematicaController;
use App\Http\Controllers\Tematica\TableTematicaController;
use App\Http\Controllers\Tematica\SaveTematicaController;

Route::middleware(['auth', 'role:admin_oc,supervisor_oc'])->group(function () {
    Route::get('/tematica', [ViewTematicaController::class, 'view'])->name('tematica');
    Route::get('/tematica/create', [ViewCreateTematicaController::class, 'create'])->name('tematica.create');
    Route::get('/tematica/edit/{id}', [ViewEditTematicaController::class, 'edit'])->name('tematica.edit');

    Route::post('/tematica/table', [TableTematicaController::class, 'table'])->name('tematica.table');
    Route::post('/tematica/save',  [SaveTematicaController::class, 'save'])->name('tematica.save');
});
