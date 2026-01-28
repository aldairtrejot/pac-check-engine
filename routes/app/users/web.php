<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUsersController;

// ... tus otras rutas arriba

Route::middleware(['auth', 'role:admin_oc'])->group(function () {

    // Vista usuarios (NOMBRE DE RUTA: usuarios) -> esto es lo que usa el menú route('usuarios')
    Route::get('/usuarios', [AdminUsersController::class, 'index'])->name('usuarios');

    // Endpoints AJAX (tabla, crear, borrar)
    Route::post('/usuarios/table',  [AdminUsersController::class, 'table'])->name('usuarios.table');
    Route::post('/usuarios/save',   [AdminUsersController::class, 'save'])->name('usuarios.save');
    Route::post('/usuarios/delete', [AdminUsersController::class, 'delete'])->name('usuarios.delete');

    // (OPCIONAL) Si quieres que el botón "Editar" no apunte a algo inexistente:
    // Route::get('/usuarios/edit/{user}', [AdminUsersController::class, 'edit'])->name('usuarios.edit');
    // Route::post('/usuarios/update/{user}', [AdminUsersController::class, 'update'])->name('usuarios.update');
});
