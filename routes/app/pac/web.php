<?php

use App\Http\Controllers\Pac\DataPacController;
use App\Http\Controllers\Pac\MainPacController;
use App\Http\Controllers\Pac\SavePacController;
use App\Http\Controllers\Pac\TablePacController;
use App\Http\Controllers\Pac\ViewPacController;
use App\Http\Controllers\Pac\CoursePacController; // 👈 NUEVO
use App\Http\Controllers\Empleado\ViewEmpleadoController;
use App\Http\Controllers\Empleado\SaveEmpleadoController;

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
    Route::post('/pac/main', [MainPacController::class, 'mainPac'])->name('pac.main');
    Route::post('/pac/table', [TablePacController::class, 'table'])->name('pac.table');
    Route::post('/pac/data', [DataPacController::class, 'dataPac'])->name('pac.data');
    Route::post('/pac/save', [SavePacController::class, 'save'])->name('pac.save');

    // 🔹 NUEVO: catálogo de cursos para el modal "Agregar curso"
    Route::post('/pac/courses', [CoursePacController::class, 'listCourses'])->name('pac.courses');

    // 🔹 NUEVO: agregar curso a empleado
    Route::post('/pac/employee/add-course', [CoursePacController::class, 'addCourseToEmployee'])->name('pac.employee.addCourse');

        Route::get('/empleado', [ViewEmpleadoController::class, 'view'])
        ->name('empleado');

    Route::post('/empleado/save', [SaveEmpleadoController::class, 'save'])
        ->name('empleado.save');
});
