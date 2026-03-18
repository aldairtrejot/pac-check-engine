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
     * ✅ ADMIN / REVISOR_EST
     */
    public function listCourses(Request $request)
    {
        try {
            $this->assertCanManageCourses();

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
            Log::error('PAC listCourses error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

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
     * - ✅ ADMIN / REVISOR_EST
     */
    public function addCourseToEmployee(Request $request)
    {
        try {
            $this->assertCanManageCourses();

            $request->validate([
                'id_empl_accion_base' => 'required|integer',
                'id_accion'           => 'required|integer',
            ]);

            $idBase   = (int) $request->id_empl_accion_base;
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

                $nextNumCurso = $maxNumCurso ? ((int) $maxNumCurso + 1) : 1;

                // 5) Nuevo id_empl_accion (si tu PK NO es serial)
                $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                $newId = $maxId ? ((int) $maxId + 1) : 1;

                // 6) Construir payload copiando base para no romper NOT NULL
                $payload = (array) $base;

                // Cambios obligatorios para “nuevo curso”
                $payload['id_empl_accion']   = $newId;
                $payload['id_accion']        = $idAccion;
                $payload['id_finalidad']     = 6;
                $payload['id_num_curso']     = $nextNumCurso;
                $payload['horas_progamadas'] = $horasProgramadas; // respeta el nombre actual de tu columna
                $payload['calificacion']     = 100;

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

                unset($payload['created_at'], $payload['updated_at']);

                DB::table('public.a2_acciones_empleados')->insert($payload);

                return response()->json([
                    'status'  => true,
                    'message' => 'Curso agregado correctamente con finalidad 6 y número de curso consecutivo.',
                ], 200);
            });

        } catch (\Throwable $th) {
            Log::error('PAC addCourseToEmployee error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al agregar el curso.',
            ], 200);
        }
    }

    /**
     * ✅ ADMIN / REVISOR_EST
     */
    private function assertCanManageCourses(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401, 'No autenticado');
        }

        if ($this->isAdminOrRevisorEst($user)) {
            return;
        }

        abort(403, 'No tienes permisos para agregar cursos.');
    }

    private function isAdminOrRevisorEst($user): bool
    {
        // Spatie
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin_oc', 'admin', 'revisor_est'])) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            if (
                $user->hasRole('admin_oc') ||
                $user->hasRole('admin') ||
                $user->hasRole('revisor_est')
            ) {
                return true;
            }
        }

        // Método auxiliar existente
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // rol_id clásico
        if (isset($user->rol_id) && (int) $user->rol_id === 1) {
            return true;
        }

        // booleano
        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        // nombre textual del rol/perfil
        $roleCandidates = [
            $user->role ?? null,
            $user->rol ?? null,
            $user->rol_nombre ?? null,
            $user->nombre_rol ?? null,
            $user->perfil ?? null,
        ];

        foreach ($roleCandidates as $role) {
            $role = strtolower(trim((string) $role));
            if (in_array($role, ['admin_oc', 'admin', 'revisor_est'], true)) {
                return true;
            }
        }

        return false;
    }
}