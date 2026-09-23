<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Support\AdscripcionCatalogs;
use App\Support\PacVisibility;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnidadCoordinacionPacController extends Controller
{
    private static array $columnsCache = [];
    private static ?bool $eventLogTableExists = null;

    public function listAdscripciones(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $idAdscripcion = (int) $request->input('id_adscripcion', 0);

            if ($idAdscripcion > 0) {
                $adscripcion = AdscripcionCatalogs::findById($idAdscripcion);

                return response()->json([
                    'status' => true,
                    'listAdscripciones' => $adscripcion ? [$adscripcion] : [],
                ], 200);
            }

            return response()->json([
                'status' => true,
                'listAdscripciones' => AdscripcionCatalogs::search($request->input('q', ''), 100),
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC listAdscripciones ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudieron cargar las adscripciones.',
                'listAdscripciones' => [],
            ], 200);
        }
    }

    public function listUnidades(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $list = DB::table('public.cat_unidades')
                ->selectRaw('id_unidad as id, nombre_unidad as descripcion')
                ->where('activo', true)
                ->orderBy('nombre_unidad', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'listUnidades' => $list,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC listUnidades ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudieron cargar las unidades.',
                'listUnidades' => [],
            ], 200);
        }
    }

    public function listCoordinaciones(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $validated = $request->validate([
                'id_unidad' => 'required|integer',
            ]);

            $list = DB::table('public.rel_unidad_coordinacion as r')
                ->join('public.cat_coordinaciones as c', 'c.id_coordinacion', '=', 'r.id_coordinacion')
                ->where('r.id_unidad', (int) $validated['id_unidad'])
                ->where('r.activo', true)
                ->where('c.activo', true)
                ->selectRaw('c.id_coordinacion as id, c.nombre_coordinacion as descripcion')
                ->orderBy('c.nombre_coordinacion', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'listCoordinaciones' => $list,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC listCoordinaciones ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudieron cargar las coordinaciones.',
                'listCoordinaciones' => [],
            ], 200);
        }
    }

    public function dataAsignacion(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $validated = $request->validate([
                'id' => 'required|integer',
            ]);

            if (! $this->canAccessEmployeeAction((int) $validated['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Acceso denegado o registro no encontrado.',
                ], 200);
            }

            $emp = DB::table('public.a2_acciones_empleados')
                ->select('id_puesto', 'curp')
                ->where('id_empl_accion', (int) $validated['id'])
                ->first();

            if (! $emp) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se encontro el empleado (a2_acciones_empleados).',
                ], 200);
            }

            $cap = DB::table('public.a2_acciones_capacitacion')
                ->select($this->capacitacionSelectColumns([
                    'id_unidad',
                    'id_coordinacion',
                    'num_cursos',
                ]))
                ->where('id_puesto', (int) $emp->id_puesto)
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
                ->first();

            $idUnidad = $cap->id_unidad ?? null;
            $idCoordinacion = $cap->id_coordinacion ?? null;
            $numCursos = $cap->num_cursos ?? null;

            $adscripcion = null;

            if ($numCursos) {
                $adscripcion = AdscripcionCatalogs::findById($numCursos);
            }

            if (! $adscripcion && $idUnidad && $idCoordinacion) {
                $adscripcion = AdscripcionCatalogs::findByUnidadCoordinacion($idUnidad, $idCoordinacion);
            }

            $adscripcionTxt = $adscripcion->adscripcion ?? '';
            $adscripcionCompl = $adscripcion->adscripcion_compl ?? '';
            $unidadTxt = $adscripcion->nombre_unidad ?? '';
            $coordinacionTxt = $adscripcion->nombre_coordinacion ?? '';

            $idAdscripcion = $adscripcion->id_adscripcion ?? null;
            $idUnidad = $adscripcion->id_unidad ?? $idUnidad;
            $idCoordinacion = $adscripcion->id_coordinacion ?? $idCoordinacion;

            if ($unidadTxt === '' && $idUnidad) {
                $unidadTxt = (string) DB::table('public.cat_unidades')
                    ->where('id_unidad', (int) $idUnidad)
                    ->value('nombre_unidad');
            }

            if ($coordinacionTxt === '' && $idCoordinacion) {
                $coordinacionTxt = (string) DB::table('public.cat_coordinaciones')
                    ->where('id_coordinacion', (int) $idCoordinacion)
                    ->value('nombre_coordinacion');
            }

            return response()->json([
                'status' => true,
                'id_adscripcion' => $idAdscripcion,
                'adscripcion' => $adscripcionTxt,
                'adscripcion_txt' => $adscripcionTxt,
                'adscripcion_compl' => $adscripcionCompl,
                'id_unidad' => $idUnidad,
                'nombre_unidad' => $unidadTxt,
                'id_coordinacion' => $idCoordinacion,
                'nombre_coordinacion' => $coordinacionTxt,
                'num_cursos' => $numCursos,
                'unidad_txt' => $unidadTxt,
                'coordinacion_txt' => $coordinacionTxt,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC dataAsignacion ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudo cargar la asignacion.',
            ], 200);
        }
    }

    public function saveAsignacion(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $user = auth()->user();

            $validated = $request->validate([
                'id' => 'required|integer',
                'id_adscripcion' => 'required|integer',
            ]);

            if (! $this->canAccessEmployeeAction((int) $validated['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Acceso denegado o registro no encontrado.',
                ], 200);
            }

            $adscripcion = AdscripcionCatalogs::findById($validated['id_adscripcion']);

            if (! $adscripcion) {
                return response()->json([
                    'status' => false,
                    'message' => 'La adscripcion seleccionada no existe en el catalogo oficial.',
                ], 200);
            }

            $emp = DB::table('public.a2_acciones_empleados')
                ->select('id_puesto', 'curp')
                ->where('id_empl_accion', (int) $validated['id'])
                ->first();

            if (! $emp) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se encontro el empleado (a2_acciones_empleados).',
                ], 200);
            }

            $targetExists = DB::table('public.a2_acciones_capacitacion')
                ->where('id_puesto', (int) $emp->id_puesto)
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
                ->exists();

            if (! $targetExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se encontro coincidencia en capacitacion por id_puesto/curp.',
                ], 200);
            }

            $numCursos = (int) $adscripcion->id_adscripcion;

            $oldAssignment = DB::table('public.a2_acciones_capacitacion')
                ->select($this->capacitacionSelectColumns([
                    'id_unidad',
                    'id_coordinacion',
                    'num_cursos',
                ]))
                ->where('id_puesto', (int) $emp->id_puesto)
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
                ->first();

            $updateAssignment = $this->filterExistingCapacitacionColumns([
                'id_unidad' => $adscripcion->id_unidad,
                'id_coordinacion' => $adscripcion->id_coordinacion,
                'num_cursos' => $numCursos,
            ]);

            DB::table('public.a2_acciones_capacitacion')
                ->where('id_puesto', (int) $emp->id_puesto)
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
                ->update($updateAssignment);

            $this->safeLogUserAction(
                userId: (int) $user->id,
                modulo: 'PAC',
                accion: 'ASIGNAR_ADSCRIPCION',
                descripcion: 'Se actualizo la adscripcion del empleado desde el catalogo oficial',
                idReferencia: (string) $validated['id'],
                payload: [
                    'id_empl_accion' => (int) $validated['id'],
                    'id_adscripcion' => (int) $adscripcion->id_adscripcion,
                    'adscripcion' => $adscripcion->adscripcion,
                    'adscripcion_compl' => $adscripcion->adscripcion_compl,
                    'id_unidad' => $adscripcion->id_unidad,
                    'unidad' => $adscripcion->nombre_unidad,
                    'id_coordinacion' => $adscripcion->id_coordinacion,
                    'coordinacion' => $adscripcion->nombre_coordinacion,
                    'num_cursos' => $numCursos,
                ],
                oldValues: $oldAssignment !== null ? (array) $oldAssignment : null,
                newValues: $updateAssignment
            );

            return response()->json([
                'status' => true,
                'message' => 'Adscripcion asignada correctamente.',
                'id_adscripcion' => (int) $adscripcion->id_adscripcion,
                'adscripcion' => $adscripcion->adscripcion,
                'adscripcion_compl' => $adscripcion->adscripcion_compl,
                'id_unidad' => $adscripcion->id_unidad,
                'nombre_unidad' => $adscripcion->nombre_unidad,
                'unidad' => $adscripcion->nombre_unidad,
                'id_coordinacion' => $adscripcion->id_coordinacion,
                'nombre_coordinacion' => $adscripcion->nombre_coordinacion,
                'coordinacion' => $adscripcion->nombre_coordinacion,
                'num_cursos' => $numCursos,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC saveAsignacion ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Ocurrio un error al guardar la asignacion.',
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

    private function assertCanManageAsignacionUnidad(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401, 'No autenticado');
        }

        if ($this->isAdminOrRevisorEst($user)) {
            return;
        }

        abort(403, 'No tienes permisos para gestionar la asignacion de unidad.');
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

    private function capacitacionSelectColumns(array $columns): array
    {
        $existing = array_flip($this->columnsFor('public', 'a2_acciones_capacitacion'));

        return array_map(
            fn (string $column) => isset($existing[$column])
                ? $column
                : DB::raw('NULL as ' . $column),
            $columns
        );
    }

    private function filterExistingCapacitacionColumns(array $values): array
    {
        $existing = array_flip($this->columnsFor('public', 'a2_acciones_capacitacion'));

        return array_filter(
            $values,
            fn (string $column) => isset($existing[$column]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function splitQualified(string $qualified): array
    {
        $qualified = trim($qualified);

        if (str_contains($qualified, '.')) {
            $parts = explode('.', $qualified);
            if (count($parts) === 2) {
                return [$parts[0], $parts[1]];
            }
        }

        return ['public', $qualified];
    }

    private function columnsFor(string $schema, string $table): array
    {
        $key = $schema . '.' . $table;

        if (! isset(self::$columnsCache[$key])) {
            $rows = DB::table('information_schema.columns')
                ->select('column_name')
                ->where('table_schema', $schema)
                ->where('table_name', $table)
                ->get();

            self::$columnsCache[$key] = $rows->pluck('column_name')
                ->map(fn ($c) => (string) $c)
                ->all();
        }

        return self::$columnsCache[$key];
    }

    private function firstExistingColumn(string $schema, string $table, array $candidates): ?string
    {
        $cols = $this->columnsFor($schema, $table);
        $set = array_flip($cols);

        foreach ($candidates as $c) {
            if (isset($set[$c])) {
                return $c;
            }
        }

        return null;
    }
}
