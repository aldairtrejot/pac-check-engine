<?php

use App\Http\Controllers\Pac\DataPacController;
use App\Http\Controllers\Pac\MainPacController;
use App\Http\Controllers\Pac\SavePacController;
use App\Http\Controllers\Pac\TablePacController;
use App\Http\Controllers\Pac\ViewPacController;
use App\Http\Controllers\Pac\CoursePacController;
use App\Http\Controllers\Empleado\ViewEmpleadoController;
use App\Http\Controllers\Empleado\SaveEmpleadoController;

/*
|--------------------------------------------------------------------------
| Routes for pac-check-engine module design
|--------------------------------------------------------------------------
| Protected by: auth
|
| Estas rutas manejan:
| - Módulo PAC (Programa Anual de Capacitación)
| - Módulo de Empleados (solo personal autorizado de RH)
*/

// ============================================================================
// RUTAS PROTEGIDAS POR AUTENTICACIÓN
// ============================================================================
Route::middleware(['auth'])->group(function () {
    
    // ========================================================================
    // MÓDULO PAC - Programa Anual de Capacitación
    // ========================================================================
    
    // Vista principal de PAC
    Route::get('/pac', [ViewPacController::class, 'viewPac'])
        ->name('pac');

    // Acciones principales de PAC
    Route::post('/pac/main', [MainPacController::class, 'mainPac'])
        ->name('pac.main');
    
    // Tabla de datos PAC
    Route::post('/pac/table', [TablePacController::class, 'table'])
        ->name('pac.table');
    
    // Datos específicos de PAC
    Route::post('/pac/data', [DataPacController::class, 'dataPac'])
        ->name('pac.data');
    
    // Guardar información de PAC
    Route::post('/pac/save', [SavePacController::class, 'save'])
        ->name('pac.save');

    // Catálogo de cursos disponibles para el modal "Agregar curso"
    Route::post('/pac/courses', [CoursePacController::class, 'listCourses'])
        ->name('pac.courses');

    // Agregar curso a un empleado específico
    Route::post('/pac/employee/add-course', [CoursePacController::class, 'addCourseToEmployee'])
        ->name('pac.employee.addCourse');

    // ========================================================================
    // MÓDULO EMPLEADOS - Gestión de plantilla de personal
    // ========================================================================
    // IMPORTANTE: Solo accesible para personal autorizado de RH
    // Los correos permitidos se configuran en ViewEmpleadoController
    
    // GET - Mostrar formulario para agregar nuevo empleado
    Route::get('/empleado', [ViewEmpleadoController::class, 'view'])
        ->name('empleado');

    // POST - Guardar nuevo empleado en la base de datos
    Route::post('/empleado/save', [SaveEmpleadoController::class, 'save'])
        ->name('empleado.save');
});