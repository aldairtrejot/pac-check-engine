<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnidadCoordinacionPacController extends Controller
{
    private static array $columnsCache = [];

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
                'trace' => $th->getTraceAsString()
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
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudieron cargar las coordinaciones.',
                'listCoordinaciones' => [],
            ], 200);
        }
    }

    /**
     * Precarga asignación actual
     */
    public function dataAsignacion(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $validated = $request->validate([
                'id' => 'required|integer',
            ]);

            $emp = DB::table('public.a2_acciones_empleados')
                ->select('id_puesto', 'curp')
                ->where('id_empl_accion', (int) $validated['id'])
                ->first();

            if (! $emp) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No se encontró el empleado (a2_acciones_empleados).',
                ], 200);
            }

            $cap = DB::table('public.a2_acciones_capacitacion')
                ->select('id_unidad', 'id_coordinacion')
                ->where('id_puesto', (int) $emp->id_puesto)
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
                ->first();

            $idUnidad = $cap->id_unidad ?? null;
            $idCoord  = $cap->id_coordinacion ?? null;

            $unidadTxt = '';
            $coordTxt  = '';

            if ($idUnidad) {
                $unidadTxt = (string) DB::table('public.cat_unidades')
                    ->where('id_unidad', (int) $idUnidad)
                    ->value('nombre_unidad');
            }

            if ($idCoord) {
                $coordTxt = (string) DB::table('public.cat_coordinaciones')
                    ->where('id_coordinacion', (int) $idCoord)
                    ->value('nombre_coordinacion');
            }

            return response()->json([
                'status'          => true,
                'id_unidad'       => $idUnidad,
                'id_coordinacion' => $idCoord,
                'unidad_txt'      => $unidadTxt,
                'coordinacion_txt'=> $coordTxt,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC dataAsignacion ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'No se pudo cargar la asignación.',
            ], 200);
        }
    }

    /**
     * Guarda asignación
     */
    public function saveAsignacion(Request $request)
    {
        try {
            $this->assertCanManageAsignacionUnidad();

            $validated = $request->validate([
                'id'              => 'required|integer',
                'id_unidad'       => 'required|integer',
                'id_coordinacion' => 'required|integer',
            ]);

            $relOk = DB::table('public.rel_unidad_coordinacion')
                ->where('id_unidad', (int) $validated['id_unidad'])
                ->where('id_coordinacion', (int) $validated['id_coordinacion'])
                ->where('activo', true)
                ->exists();

            if (! $relOk) {
                return response()->json([
                    'status'  => false,
                    'message' => 'La coordinación seleccionada no pertenece a la unidad (o está inactiva).',
                ], 200);
            }

            $emp = DB::table('public.a2_acciones_empleados')
                ->select('id_puesto', 'curp')
                ->where('id_empl_accion', (int) $validated['id'])
                ->first();

            if (! $emp) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No se encontró el empleado (a2_acciones_empleados).',
                ], 200);
            }

            $updated = DB::table('public.a2_acciones_capacitacion')
                ->where('id_puesto', (int) $emp->id_puesto)
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
                ->update([
                    'id_unidad'       => (int) $validated['id_unidad'],
                    'id_coordinacion' => (int) $validated['id_coordinacion'],
                ]);

            if (! $updated) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No se actualizó (no se encontró coincidencia en capacitación por id_puesto/curp).',
                ], 200);
            }

            $unidadTxt = (string) DB::table('public.cat_unidades')
                ->where('id_unidad', (int) $validated['id_unidad'])
                ->value('nombre_unidad');

            $coordTxt = (string) DB::table('public.cat_coordinaciones')
                ->where('id_coordinacion', (int) $validated['id_coordinacion'])
                ->value('nombre_coordinacion');

            return response()->json([
                'status'       => true,
                'message'      => 'Unidad y coordinación asignadas correctamente.',
                'unidad'       => $unidadTxt,
                'coordinacion' => $coordTxt,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('PAC saveAsignacion ERROR: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al guardar la asignación.',
            ], 200);
        }
    }

    /**
     * ✅ ADMIN / REVISOR_EST
     */
    private function assertCanManageAsignacionUnidad(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401, 'No autenticado');
        }

        if ($this->isAdminOrRevisorEst($user)) {
            return;
        }

        abort(403, 'No tienes permisos para gestionar la asignación de unidad.');
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
        $set  = array_flip($cols);

        foreach ($candidates as $c) {
            if (isset($set[$c])) {
                return $c;
            }
        }

        return null;
    }
}