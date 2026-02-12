<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoursePacController extends Controller
{
    /**
     * Catálogo de cursos para el modal "Agregar curso".
     * ✅ Sin filtro: se puede agregar cualquiera.
     * ✅ Devuelve id + descripcion + text (por compatibilidad con tu inputSelect).
     * ✅ SOLO ADMIN
     */
    public function listCourses(Request $request)
    {
        try {
            $this->assertAdminCanAddCourses();

            $rows = DB::table('public.a1_cat_acciones')
                ->selectRaw("
                    id_accion as id,
                    nombre_accion as descripcion,
                    nombre_accion as text
                ")
                ->orderBy('nombre_accion', 'ASC')
                ->get();

            return response()->json([
                'status'      => true,
                'listCourses' => $rows,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC listCourses error: '.$th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return response()->json([
                'status'  => false,
                'message' => 'No se pudieron cargar los cursos.',
            ], 200);
        }
    }

    /**
     * Agrega un curso (acción) a un empleado.
     * - ✅ Copia el registro base (evita NOT NULL)
     * - ✅ Evita duplicados (mismo empleado + misma acción)
     * - ✅ id_finalidad SIEMPRE = 6
     * - ✅ Genera consecutivo id_num_curso para ese empleado
     * - ✅ Mantiene horas_programadas desde catálogo
     * - ✅ SOLO ADMIN
     */
    public function addCourseToEmployee(Request $request)
    {
        try {
            $this->assertAdminCanAddCourses();

            $request->validate([
                'id_empl_accion_base' => 'required|integer',
                'id_accion'           => 'required|integer',
            ]);

            $idBase  = (int) $request->id_empl_accion_base;
            $idAccion = (int) $request->id_accion;

            return DB::transaction(function () use ($idBase, $idAccion) {

                // 1) Registro base del empleado
                $base = DB::table('public.a2_acciones_empleados')
                    ->where('id_empl_accion', $idBase)
                    ->first();

                if (! $base) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Registro base del empleado no encontrado.',
                    ], 200);
                }

                // 2) Datos de la acción (horas)
                $accion = DB::table('public.a1_cat_acciones')
                    ->select('duracion_hrs')
                    ->where('id_accion', $idAccion)
                    ->first();

                if (! $accion) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Acción seleccionada no encontrada.',
                    ], 200);
                }

                $horasProgramadas = $accion->duracion_hrs ?? null;

                // 3) Evitar duplicados: mismo empleado + misma acción
                $existe = DB::table('public.a2_acciones_empleados')
                    ->where('id_puesto', $base->id_puesto)
                    ->where('curp', $base->curp)
                    ->where('id_accion', $idAccion)
                    ->exists();

                if ($existe) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Este curso ya está asignado al empleado.',
                    ], 200);
                }

                // 4) Consecutivo id_num_curso para ese empleado
                $maxNumCurso = DB::table('public.a2_acciones_empleados')
                    ->where('id_puesto', $base->id_puesto)
                    ->where('curp', $base->curp)
                    ->max('id_num_curso');

                $nextNumCurso = $maxNumCurso ? ((int)$maxNumCurso + 1) : 1;

                // 5) Nuevo id_empl_accion (si tu PK NO es serial)
                //    Si tu PK es serial/identity, podemos quitar esto y usar insertGetId().
                $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                $newId = $maxId ? ((int)$maxId + 1) : 1;

                // 6) Construir payload copiando base para no romper NOT NULL
                $payload = (array) $base;

                // Cambios obligatorios para “nuevo curso”
                $payload['id_empl_accion']   = $newId;
                $payload['id_accion']        = $idAccion;
                $payload['id_finalidad']     = 6;               // ✅ regla
                $payload['id_num_curso']     = $nextNumCurso;    // ✅ consecutivo
                $payload['horas_progamadas'] = $horasProgramadas; // ✅ respeta tu nombre de columna
                $payload['calificacion']     = 100;             // por default

                // Limpiar campos que deben empezar vacíos
                $payload['horas_real']       = null;
                $payload['id_instancia']     = null;
                $payload['costo_unitario']   = null;
                $payload['fecha_ini']        = null;
                $payload['fecha_fin']        = null;
                $payload['id_trimestre']     = null;
                $payload['eval_aprendizaje'] = null;
                $payload['observaciones']    = null;
                $payload['id_cat_estatus']   = null;
                $payload['id_cat_tematica']  = null;

                // Si tu tabla tiene timestamps y NO quieres copiarlos:
                unset($payload['created_at'], $payload['updated_at']);

                // Insert
                DB::table('public.a2_acciones_empleados')->insert($payload);

                return response()->json([
                    'status'  => true,
                    'message' => 'Curso agregado correctamente con finalidad 6 y número de curso consecutivo.',
                ], 200);
            });

        } catch (\Throwable $th) {
            Log::error('PAC addCourseToEmployee error: '.$th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al agregar el curso.',
            ], 200);
        }
    }

    /**
     * ✅ Solo admin puede agregar cursos.
     * Ajusta si tu proyecto usa roles distintos.
     */
    private function assertAdminCanAddCourses(): void
    {
        $user = auth()->user();
        if (! $user) abort(401, 'No autenticado');

        // Spatie (si existe)
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin_oc')) return;
        }

        // Si tienes método isAdmin
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) return;

        // Si tienes rol_id
        if (isset($user->rol_id) && (int)$user->rol_id === 1) return;

        // Si tienes is_admin boolean
        if (isset($user->is_admin) && (bool)$user->is_admin) return;

        abort(403, 'Solo el administrador puede agregar cursos.');
    }
}