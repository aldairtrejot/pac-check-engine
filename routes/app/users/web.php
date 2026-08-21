<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUsersController;

Route::middleware(['auth', 'role:admin_oc'])->group(function () {

    Route::get('/usuarios', [AdminUsersController::class, 'index'])->name('usuarios');

    Route::post('/usuarios/options', [AdminUsersController::class, 'options'])->name('usuarios.options');
    Route::post('/usuarios/table', [AdminUsersController::class, 'table'])->name('usuarios.table');
    Route::post('/usuarios/save', [AdminUsersController::class, 'save'])->name('usuarios.save');
    Route::post('/usuarios/update', [AdminUsersController::class, 'update'])->name('usuarios.update');
    Route::post('/usuarios/toggle-status', [AdminUsersController::class, 'toggleStatus'])->name('usuarios.toggle-status');
    Route::post('/usuarios/delete', [AdminUsersController::class, 'delete'])->name('usuarios.delete');
});
