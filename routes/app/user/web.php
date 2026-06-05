<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MiSesionController;

Route::middleware(['auth'])->group(function () {

    Route::get('/mi-sesion', [MiSesionController::class, 'index'])
        ->name('mi-sesion');

});