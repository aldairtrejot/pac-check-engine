<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\EntityPacModel;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoursePacController extends Controller
{
    public function listCourses()
    {
        try {
            $rows = DB::table('public.a1_cat_acciones')
                ->select(
                    'id_accion as id',
                    'nombre_accion as descripcion'
                )
                ->where(function ($q) {
                    $q->whereRaw("TRIM(UPPER(estatus)) = 'VIGENTE'")
                      ->orWhereRaw("TRIM(UPPER(estatus)) = 'ALTA'");
                })
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

    public function addCourseToEmployee(Request $request)
    {
        try {
            $request->validate([
                'id_empl_accion_base' => 'required|integer',
                'id_accion'           => 'required|integer',
            ]);

            // ✅ CERRAR BACKEND: validar que el registro base es visible para el usuario
            $this->authorizePacRecord((int) $request->id_empl_accion_base, $request->user());

            // 1) Registro base del empleado
            $base = DB::table('public.a2_acciones_empleados')
                ->where('id_empl_accion', (int) $request->id_empl_accion_base)
                ->first();

            if (! $base) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Registro base del empleado no encontrado.',
                ], 200);
            }

            // 2) Datos de la acción
            $accion = DB::table('public.a1_cat_acciones')
                ->select('duracion_hrs')
                ->where('id_accion', (int) $request->id_accion)
                ->first();

            if (! $accion) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Acción seleccionada no encontrada.',
                ], 200);
            }

            $horasProgramadas = $accion->duracion_hrs ?? null;

            // 3) Evitar duplicados
            $existe = DB::table('public.a2_acciones_empleados')
                ->where('id_puesto', $base->id_puesto)
                ->where('curp', $base->curp)
                ->where('id_accion', (int) $request->id_accion)
                ->exists();

            if ($existe) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Este curso ya está asignado al empleado.',
                ], 200);
            }

            // 4) Consecutivo id_num_curso
            $maxNumCurso = DB::table('public.a2_acciones_empleados')
                ->where('id_puesto', $base->id_puesto)
                ->where('curp', $base->curp)
                ->max('id_num_curso');

            $nextNumCurso = $maxNumCurso ? $maxNumCurso + 1 : 1;

            // 5) Nuevo id_empl_accion
            $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
            $newId = $maxId ? $maxId + 1 : 1;

            // 6) Crear registro
            $entity = new EntityPacModel();
            $entity->id_empl_accion   = $newId;
            $entity->id_puesto        = $base->id_puesto;
            $entity->curp             = $base->curp;
            $entity->id_accion        = (int) $request->id_accion;

            // REGLA: siempre 6
            $entity->id_finalidad     = 6;

            $entity->horas_real       = null;
            $entity->id_instancia     = null;
            $entity->costo_unitario   = null;
            $entity->fecha_ini        = null;
            $entity->fecha_fin        = null;
            $entity->id_trimestre     = null;
            $entity->id_num_curso     = $nextNumCurso;
            $entity->eval_aprendizaje = null;
            $entity->observaciones    = null;
            $entity->id_cat_estatus   = null;
            $entity->id_cat_tematica  = null;
            $entity->horas_progamadas = $horasProgramadas;

            $entity->save();

            return response()->json([
                'status'  => true,
                'message' => 'Curso agregado correctamente con finalidad 6 y número de curso consecutivo.',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al agregar el curso.',
            ], 200);
        }
    }

    private function authorizePacRecord(int $idEmplAccion, $user): void
    {
        $q = DB::table('public.a2_acciones_empleados')
            ->join('public.a2_acciones_capacitacion', function ($join) {
                $join->on(
                    DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                    '=',
                    'public.a2_acciones_capacitacion.id_puesto'
                );
            })
            ->where('public.a2_acciones_empleados.id_empl_accion', $idEmplAccion);

        PacVisibility::apply($q, $user, 'public.a2_acciones_capacitacion');

        if (! $q->exists()) {
            abort(403, 'No autorizado para operar sobre este empleado/registro.');
        }
    }
}