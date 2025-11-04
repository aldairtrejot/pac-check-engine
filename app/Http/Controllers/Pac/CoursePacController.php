<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\EntityPacModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePacController extends Controller
{
    /**
     * Devuelve el catálogo de cursos (acciones) para el combo.
     */
    public function listCourses()
    {
        try {
            $rows = DB::table('public.a1_cat_acciones')
                ->select(
                    'id_accion as id',
                    'nombre_accion as descripcion'
                )
                ->orderBy('nombre_accion', 'ASC')
                ->get();

            return response()->json([
                'status'      => true,
                'listCourses' => $rows,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'No se pudieron cargar los cursos.',
            ], 200);
        }
    }

    /**
     * Agrega un curso (acción) a un empleado.
     *
     * Reglas:
     *  - Usa un registro base (id_empl_accion_base).
     *  - Calcula el siguiente id_empl_accion (MAX + 1).
     *  - Calcula el siguiente id_num_curso para ese empleado (por CURP).
     *  - Asigna id_finalidad = 6 por defecto.
     *  - Valida que el curso no esté ya registrado para ese empleado.
     */
    public function addCourseToEmployee(Request $request)
    {
        try {
            $request->validate([
                'id_empl_accion_base' => 'required|integer',
                'id_accion'           => 'required|integer',
            ]);

            // 1) Registro base del empleado
            $base = DB::table('public.a2_acciones_empleados')
                ->where('id_empl_accion', $request->id_empl_accion_base)
                ->first();

            if (! $base) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Registro base del empleado no encontrado.',
                ], 200);
            }

            // 2) Verificar que el curso no exista ya para este empleado (CURP + id_accion)
            $exists = DB::table('public.a2_acciones_empleados')
                ->where('curp', $base->curp)
                ->where('id_accion', $request->id_accion)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Este curso ya está registrado para este empleado.',
                ], 200);
            }

            // 3) Obtener horas programadas de la acción
            $accion = DB::table('public.a1_cat_acciones')
                ->select('duracion_hrs')
                ->where('id_accion', $request->id_accion)
                ->first();

            $horasProgramadas = $accion->duracion_hrs ?? null;

            // 4) Generar nuevo id_empl_accion (sin tocar la estructura de la tabla)
            $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
            $newId = $maxId ? $maxId + 1 : 1;

            // 5) Calcular el siguiente id_num_curso para este empleado (por CURP)
            $maxNumCurso = DB::table('public.a2_acciones_empleados')
                ->where('curp', $base->curp)
                ->max('id_num_curso');

            $newNumCurso = $maxNumCurso ? $maxNumCurso + 1 : 1;

            // 6) Crear nuevo registro en a2_acciones_empleados
            $entity = new EntityPacModel();
            $entity->id_empl_accion  = $newId;
            $entity->id_puesto       = $base->id_puesto;
            $entity->curp            = $base->curp;
            $entity->id_accion       = $request->id_accion;
            $entity->id_finalidad    = 6;              // 👈 Finalidad por defecto
            $entity->horas_real      = null;
            $entity->id_instancia    = null;
            $entity->costo_unitario  = null;
            $entity->fecha_ini       = null;
            $entity->fecha_fin       = null;
            $entity->id_trimestre    = null;
            $entity->id_num_curso    = $newNumCurso;   // 👈 Número de curso consecutivo
            $entity->eval_aprendizaje = false;
            $entity->observaciones   = null;
            $entity->id_cat_estatus  = null;
            $entity->id_cat_tematica = null;
            $entity->horas_progamadas = $horasProgramadas;

            $entity->save();

            return response()->json([
                'status'  => true,
                'message' => 'Curso agregado correctamente con ID generado y finalidad asignada.',
            ], 200);
        } catch (\Throwable $th) {
            // \Log::info($th);
            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al agregar el curso.',
            ], 200);
        }
    }
}
