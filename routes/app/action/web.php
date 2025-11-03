<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Action\ViewActioController;
use App\Http\Controllers\Action\ViewCreateActionController;
use App\Http\Controllers\Action\ViewEditActionController;
use App\Http\Controllers\Action\TableActionController;
use App\Http\Controllers\Action\SaveActionController; // 👈 agrega esto

Route::middleware(['auth'])->group(function () {

    Route::get('/action', [ViewActioController::class, 'view'])->name('action');
    Route::get('/action/create', [ViewCreateActionController::class, 'create'])->name('action.create');
    Route::get('/action/edit/{id}', [ViewEditActionController::class, 'edit'])->name('action.edit');

    Route::post('/action/table', [TableActionController::class, 'table'])->name('action.table');

    // 🔹 NUEVA RUTA PARA GUARDAR (crear / editar)
    Route::post('/action/save', [SaveActionController::class, 'save'])->name('action.save');
});
