<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Support\PacVisibility;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CoursePacController extends Controller
{
    private static ?bool $eventLogTableExists = null;

    /**
     * Catálogo de cursos para el modal "Agregar curso".
     * Solo muestra cursos con estatus VIGENTE.
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
                ->whereRaw("TRIM(UPPER(COALESCE(a.estatus, ''))) = 'VIGENTE'")
                ->orderBy('a.nombre_accion', 'ASC')
                ->get();

            return response()->json([
                'status'      => true,
                'listCourses' => $rows,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC listCourses error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
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
     * Solo permite cursos con estatus VIGENTE.
     */
    public function addCourseToEmployee(Request $request)
    {
        try {
            $this->assertCanManageCourses();

            $user = auth()->user();

            $validated = $request->validate([
                'id_empl_accion_base' => 'required|integer',
                'id_accion'           => 'required|integer',
            ]);

            $idBase   = (int) $validated['id_empl_accion_base'];
            $idAccion = (int) $validated['id_accion'];

            if (! $this->canAccessEmployeeAction($idBase)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Acceso denegado o registro no encontrado.',
                ], 200);
            }

            $result = DB::transaction(function () use ($idBase, $idAccion) {
                $base = DB::table('public.a2_acciones_empleados')
                    ->where('id_empl_accion', $idBase)
                    ->lockForUpdate()
                    ->first();

                if (! $base) {
                    return [
                        'status'  => false,
                        'message' => 'Registro base del empleado no encontrado.',
                    ];
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
                    return [
                        'status'  => false,
                        'message' => 'Acción seleccionada no encontrada.',
                    ];
                }

                $estatusAccion = mb_strtoupper(trim((string) $accion->estatus), 'UTF-8');

                if ($estatusAccion !== 'VIGENTE') {
                    return [
                        'status'  => false,
                        'message' => 'Solo se pueden agregar cursos con estatus VIGENTE.',
                    ];
                }

                $employeeRows = DB::table('public.a2_acciones_empleados')
                    ->select('id_num_curso', 'id_accion')
                    ->where('id_puesto', $base->id_puesto)
                    ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$base->curp])
                    ->lockForUpdate()
                    ->get();

                $existe = $employeeRows->contains(function ($row) use ($idAccion) {
                    return (int) $row->id_accion === $idAccion;
                });

                if ($existe) {
                    return [
                        'status'  => false,
                        'message' => 'Este curso ya está asignado al empleado.',
                    ];
                }

                $maxNumCurso = $employeeRows
                    ->pluck('id_num_curso')
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->map(fn ($v) => (int) $v)
                    ->max();

                $nextNumCurso = $maxNumCurso ? ($maxNumCurso + 1) : 1;

                DB::statement('LOCK TABLE public.a2_acciones_empleados IN ACCESS EXCLUSIVE MODE');

                $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                $newId = $maxId ? ((int) $maxId + 1) : 1;

                $payload = (array) $base;

                $payload['id_empl_accion']   = $newId;
                $payload['id_accion']        = $idAccion;
                $payload['id_finalidad']     = 6;
                $payload['id_num_curso']     = $nextNumCurso;
                $payload['horas_progamadas'] = $accion->duracion_hrs ?? null;
                $payload['calificacion']     = 100.00;

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

                unset(
                    $payload['created_at'],
                    $payload['updated_at']
                );

                DB::table('public.a2_acciones_empleados')->insert($payload);

                return [
                    'status'        => true,
                    'message'       => 'Curso agregado correctamente.',
                    'id_nuevo'      => $newId,
                    'id_base'       => $idBase,
                    'id_accion'     => $idAccion,
                    'id_num_curso'  => $nextNumCurso,
                    'duracion_hrs'  => $accion->duracion_hrs ?? null,
                    'new_values'    => $payload,
                ];
            });

            if (! $result['status']) {
                return response()->json([
                    'status'  => false,
                    'message' => $result['message'],
                ], 200);
            }

            $this->safeLogUserAction(
                userId: (int) $user->id,
                modulo: 'PAC',
                accion: 'AGREGAR_CURSO',
                descripcion: 'Se agregó un curso a un empleado',
                idReferencia: (string) $result['id_nuevo'],
                payload: [
                    'id_empl_accion_base' => $result['id_base'],
                    'id_empl_accion_nuevo'=> $result['id_nuevo'],
                    'id_accion'           => $result['id_accion'],
                    'id_num_curso'        => $result['id_num_curso'],
                    'horas_programadas'   => $result['duracion_hrs'],
                    'id_finalidad'        => 6,
                    'calificacion'        => 100.00,
                ],
                newValues: $result['new_values'] ?? null
            );

            return response()->json([
                'status'  => true,
                'message' => $result['message'],
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            Log::error('PAC addCourseToEmployee error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al agregar el curso.',
            ], 200);
        }
    }

    private function safeLogUserAction(
        int $userId,
        string $modulo,
        string $accion,
        ?string $descripcion = null,
        ?string $idReferencia = null,
        ?array $payload = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        UserActionLogger::write(
            idUsuario: $userId,
            modulo: $modulo,
            accion: $accion,
            descripcion: $descripcion,
            idReferencia: $idReferencia,
            payload: $payload,
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    private function eventLogTableExists(): bool
    {
        if (self::$eventLogTableExists !== null) {
            return self::$eventLogTableExists;
        }

        try {
            self::$eventLogTableExists = DB::table('information_schema.tables')
                ->where('table_schema', 'log')
                ->where('table_name', 'log_eventos_usuario')
                ->exists();
        } catch (\Throwable $e) {
            self::$eventLogTableExists = false;
        }

        return self::$eventLogTableExists;
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

    private function canAccessEmployeeAction(int $idEmplAccion): bool
    {
        $user = auth()->user();

        if (! $user || $idEmplAccion <= 0) {
            return false;
        }

        $query = DB::table('public.a2_acciones_empleados as e')
            ->join('public.a2_acciones_capacitacion as c', function ($join) {
                $join->on(
                    DB::raw("
                        CASE
                            WHEN TRIM(e.id_puesto) ~ '^[0-9]+$'
                            THEN TRIM(e.id_puesto)::INTEGER
                            ELSE NULL
                        END
                    "),
                    '=',
                    'c.id_puesto'
                )->whereRaw(
                    'UPPER(TRIM(public.unaccent(e.curp))) = UPPER(TRIM(public.unaccent(c.curp)))'
                );
            })
            ->where('e.id_empl_accion', $idEmplAccion);

        PacVisibility::apply(
            $query,
            $user,
            'c',
            'public.a2_acciones_capacitacion'
        );

        return $query->exists();
    }

    private function isAdminOrRevisorEst($user): bool
    {
        if (! $user) {
            return false;
        }

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
