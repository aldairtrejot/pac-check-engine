<?php

use App\Http\Controllers\Pac\DataPacController;
use App\Http\Controllers\Pac\MainPacController;
use App\Http\Controllers\Pac\SavePacController;
use App\Http\Controllers\Pac\TablePacController;
use App\Http\Controllers\Pac\ViewPacController;
use App\Http\Controllers\Pac\CoursePacController;
use App\Http\Controllers\Empleado\ViewEmpleadoController;
use App\Http\Controllers\Empleado\SaveEmpleadoController;
use App\Http\Controllers\Pac\UnidadCoordinacionPacController;

Route::middleware(['auth'])->group(function () {

    // PAC para TODOS (centrales y operativos)
    Route::get('/pac', [ViewPacController::class, 'viewPac'])->name('pac');
    Route::post('/pac/main', [MainPacController::class, 'mainPac'])->name('pac.main');
    Route::post('/pac/table', [TablePacController::class, 'table'])->name('pac.table');
    Route::post('/pac/data', [DataPacController::class, 'dataPac'])->name('pac.data');
    Route::post('/pac/save', [SavePacController::class, 'save'])->name('pac.save');

    Route::post('/pac/courses', [CoursePacController::class, 'listCourses'])->name('pac.courses');
    Route::post('/pac/employee/add-course', [CoursePacController::class, 'addCourseToEmployee'])->name('pac.employee.addCourse');

    // EMPLEADO solo Centrales
    Route::middleware(['role:admin_oc,supervisor_oc'])->group(function () {
        Route::get('/empleado', [ViewEmpleadoController::class, 'view'])->name('empleado');
        Route::post('/empleado/save', [SaveEmpleadoController::class, 'save'])->name('empleado.save');
    });
Route::post('/pac/data', [DataPacController::class, 'dataPac']);

Route::post('/pac/unidades', [UnidadCoordinacionPacController::class, 'listUnidades']);
Route::post('/pac/coordinaciones', [UnidadCoordinacionPacController::class, 'listCoordinaciones']);
Route::post('/pac/asignacion-unidad/data', [UnidadCoordinacionPacController::class, 'dataAsignacion']);
Route::post('/pac/asignacion-unidad/save', [UnidadCoordinacionPacController::class, 'saveAsignacion']);

});