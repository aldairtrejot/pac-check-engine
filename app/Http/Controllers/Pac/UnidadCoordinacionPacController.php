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
            $this->assertAdminCanAsignarUnidad();

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
            Log::error('PAC listUnidades ERROR: '.$th->getMessage(), ['trace' => $th->getTraceAsString()]);

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
            $this->assertAdminCanAsignarUnidad();

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
            Log::error('PAC listCoordinaciones ERROR: '.$th->getMessage(), ['trace' => $th->getTraceAsString()]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudieron cargar las coordinaciones.',
                'listCoordinaciones' => [],
            ], 200);
        }
    }

    /**
     * ✅ Precarga asignación actual (lee unidad/coordinacion desde a2_acciones_capacitacion)
     * Retorna:
     * - id_unidad, id_coordinacion
     * - unidad_txt, coordinacion_txt
     */
   public function dataAsignacion(Request $request)
{
    try {
        $this->assertAdminCanAsignarUnidad();

        $validated = $request->validate([
            'id' => 'required|integer', // ESTE id ES id_empl_accion
        ]);

        // 1) Resolver empleado desde a2_acciones_empleados
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

        // 2) Buscar fila en a2_acciones_capacitacion por id_puesto (y curp opcional)
        $cap = DB::table('public.a2_acciones_capacitacion')
            ->select('unidad as unidad_txt', 'coordinacion as coordinacion_txt')
            ->where('id_puesto', (int) $emp->id_puesto)
            // si quieres amarrarlo más, deja esta línea:
            ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
            ->first();

        if (! $cap) {
            return response()->json([
                'status'  => true,
                'id_unidad' => null,
                'id_coordinacion' => null,
                'unidad_txt' => '',
                'coordinacion_txt' => '',
            ], 200);
        }

        // 3) Texto -> IDs (para precargar selects)
        $idUnidad = null;
        $idCoord  = null;

        if (!empty($cap->unidad_txt)) {
            $idUnidad = DB::table('public.cat_unidades')
                ->whereRaw('TRIM(UPPER(nombre_unidad)) = TRIM(UPPER(?))', [$cap->unidad_txt])
                ->value('id_unidad');
        }

        if (!empty($cap->coordinacion_txt)) {
            $idCoord = DB::table('public.cat_coordinaciones')
                ->whereRaw('TRIM(UPPER(nombre_coordinacion)) = TRIM(UPPER(?))', [$cap->coordinacion_txt])
                ->value('id_coordinacion');
        }

        return response()->json([
            'status' => true,
            'id_unidad' => $idUnidad,
            'id_coordinacion' => $idCoord,
            'unidad_txt' => (string) ($cap->unidad_txt ?? ''),
            'coordinacion_txt' => (string) ($cap->coordinacion_txt ?? ''),
        ], 200);

    } catch (\Throwable $th) {
        Log::error('PAC dataAsignacion ERROR: '.$th->getMessage(), ['trace' => $th->getTraceAsString()]);

        return response()->json([
            'status'  => false,
            'message' => 'No se pudo cargar la asignación.',
        ], 200);
    }
}

    /**
     * ✅ Guarda asignación (escribe texto) en a2_acciones_capacitacion.unidad / coordinacion
     */
   public function saveAsignacion(Request $request)
{
    try {
        $this->assertAdminCanAsignarUnidad();

        $validated = $request->validate([
            'id'              => 'required|integer', // ESTE id ES id_empl_accion
            'id_unidad'       => 'required|integer',
            'id_coordinacion' => 'required|integer',
        ]);

        // valida relación activa
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

        // nombres (texto)
        $unidadTxt = DB::table('public.cat_unidades')
            ->where('id_unidad', (int) $validated['id_unidad'])
            ->value('nombre_unidad');

        $coordTxt = DB::table('public.cat_coordinaciones')
            ->where('id_coordinacion', (int) $validated['id_coordinacion'])
            ->value('nombre_coordinacion');

        if (! $unidadTxt || ! $coordTxt) {
            return response()->json([
                'status'  => false,
                'message' => 'No se pudo resolver el nombre de unidad o coordinación.',
            ], 200);
        }

        // 1) Resolver empleado desde a2_acciones_empleados
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

        // 2) Actualizar a2_acciones_capacitacion por id_puesto (+curp)
        $updated = DB::table('public.a2_acciones_capacitacion')
            ->where('id_puesto', (int) $emp->id_puesto)
            ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$emp->curp])
            ->update([
                'unidad' => (string) $unidadTxt,
                'coordinacion' => (string) $coordTxt,
            ]);

        if (! $updated) {
            return response()->json([
                'status'  => false,
                'message' => 'No se actualizó el registro (no se encontró coincidencia en capacitación por id_puesto/curp).',
            ], 200);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Unidad y coordinación asignadas correctamente.',
            'unidad'  => (string) $unidadTxt,
            'coordinacion' => (string) $coordTxt,
        ], 200);

    } catch (\Throwable $th) {
        Log::error('PAC saveAsignacion ERROR: '.$th->getMessage(), ['trace' => $th->getTraceAsString()]);

        return response()->json([
            'status'  => false,
            'message' => 'Ocurrió un error al guardar la asignación.',
        ], 200);
    }
}

    /**
     * ✅ Permiso: solo admin (mismo estilo que tu CoursePacController)
     */
    private function assertAdminCanAsignarUnidad(): void
    {
        $user = auth()->user();
        if (! $user) abort(401, 'No autenticado');

        // Spatie
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin_oc')) return;
        }

        // isAdmin()
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) return;

        // rol_id
        if (isset($user->rol_id) && (int)$user->rol_id === 1) return;

        // is_admin boolean
        if (isset($user->is_admin) && (bool)$user->is_admin) return;

        abort(403, 'Solo el administrador puede asignar unidad.');
    }

    private function splitQualified(string $qualified): array
    {
        $qualified = trim($qualified);
        if (str_contains($qualified, '.')) {
            $parts = explode('.', $qualified);
            if (count($parts) === 2) return [$parts[0], $parts[1]];
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

            self::$columnsCache[$key] = $rows->pluck('column_name')->map(fn ($c) => (string) $c)->all();
        }

        return self::$columnsCache[$key];
    }

    private function firstExistingColumn(string $schema, string $table, array $candidates): ?string
    {
        $cols = $this->columnsFor($schema, $table);
        $set  = array_flip($cols);

        foreach ($candidates as $c) {
            if (isset($set[$c])) return $c;
        }
        return null;
    }
}