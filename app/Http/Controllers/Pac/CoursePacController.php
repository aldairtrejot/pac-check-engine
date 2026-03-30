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
     * Solo muestra cursos con estatus VIGENTE o ALTA.
     */
    public function listCourses(Request $request)
    {
        try {
            $this->assertCanManageCourses();

            $rows = DB::table('public.a1_cat_acciones as a')
                ->selectRaw("
                    a.id_accion as id,
                    a.nombre_accion as descripcion,
                    a.nombre_accion as text,
                    a.estatus as estatus
                ")
                ->where(function ($q) {
                    $q->whereRaw("UPPER(TRIM(a.estatus)) = 'VIGENTE'")
                      ->orWhereRaw("UPPER(TRIM(a.estatus)) = 'ALTA'");
                })
                ->orderBy('a.nombre_accion', 'ASC')
                ->get();

            return response()->json([
                'status'      => true,
                'listCourses' => $rows,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC listCourses error: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status'      => false,
                'message'     => 'No se pudieron cargar los cursos.',
                'listCourses' => [],
            ], 200);
        }
    }

    /**
     * Agrega un curso (acción) a un empleado.
     * Solo permite cursos con estatus VIGENTE o ALTA.
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

                $base = DB::table('public.a2_acciones_empleados')
                    ->where('id_empl_accion', $idBase)
                    ->first();

                if (! $base) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Registro base del empleado no encontrado.',
                    ], 200);
                }

                $accion = DB::table('public.a1_cat_acciones as a')
                    ->select(
                        'a.id_accion',
                        'a.duracion_hrs',
                        'a.estatus'
                    )
                    ->where('a.id_accion', $idAccion)
                    ->first();

                if (! $accion) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Acción seleccionada no encontrada.',
                    ], 200);
                }

                $estatusAccion = mb_strtoupper(trim((string) $accion->estatus), 'UTF-8');

                if (! in_array($estatusAccion, ['VIGENTE', 'ALTA'], true)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Solo se pueden agregar cursos con estatus VIGENTE o ALTA.',
                    ], 200);
                }

                $horasProgramadas = $accion->duracion_hrs ?? null;

                $existe = DB::table('public.a2_acciones_empleados')
                    ->where('id_puesto', $base->id_puesto)
                    ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$base->curp])
                    ->where('id_accion', $idAccion)
                    ->exists();

                if ($existe) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Este curso ya está asignado al empleado.',
                    ], 200);
                }

                $maxNumCurso = DB::table('public.a2_acciones_empleados')
                    ->where('id_puesto', $base->id_puesto)
                    ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$base->curp])
                    ->max('id_num_curso');

                $nextNumCurso = $maxNumCurso ? ((int) $maxNumCurso + 1) : 1;

                $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                $newId = $maxId ? ((int) $maxId + 1) : 1;

                $payload = (array) $base;

                $payload['id_empl_accion']   = $newId;
                $payload['id_accion']        = $idAccion;
                $payload['id_finalidad']     = 6;
                $payload['id_num_curso']     = $nextNumCurso;
                $payload['horas_progamadas'] = $horasProgramadas;
                $payload['calificacion']     = 100;

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
                    'message' => 'Curso agregado correctamente.',
                ], 200);
            });

        } catch (\Throwable $th) {
            Log::error('PAC addCourseToEmployee error: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al agregar el curso.',
            ], 200);
        }
    }

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

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (isset($user->rol_id) && (int) $user->rol_id === 1) {
            return true;
        }

        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

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