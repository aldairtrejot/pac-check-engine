<?php

use App\Http\Controllers\Instancia\ViewInstanciaController;
use App\Http\Controllers\Instancia\ViewCreateInstanciaController;
use App\Http\Controllers\Instancia\ViewEditInstanciaController;
use App\Http\Controllers\Instancia\TableInstanciaController;
use App\Http\Controllers\Instancia\SaveInstanciaController;

/*
|--------------------------------------------------------------------------
| Rutas para catálogo de instancias
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/instancia', [ViewInstanciaController::class, 'view'])->name('instancia');
    Route::get('/instancia/create', [ViewCreateInstanciaController::class, 'create'])->name('instancia.create');
    Route::get('/instancia/edit/{id}', [ViewEditInstanciaController::class, 'edit'])->name('instancia.edit');

    Route::post('/instancia/table', [TableInstanciaController::class, 'table'])->name('instancia.table');
    Route::post('/instancia/save', [SaveInstanciaController::class, 'save'])->name('instancia.save');
});
