<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnidadCoordinacionPacController extends Controller
{
    private static array $columnsCache = [];

    public function listUnidades(Request $request)
    {
        try {
            $user = auth()->user();

            if (!PacVisibility::canAddCourse($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Solo el administrador puede asignar unidad.',
                    'listUnidades' => [],
                ], 403);
            }

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
            $user = auth()->user();

            if (!PacVisibility::canAddCourse($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Solo el administrador puede asignar coordinaciones.',
                    'listCoordinaciones' => [],
                ], 403);
            }

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

    public function saveAsignacion(Request $request)
    {
        try {
            $user = auth()->user();

            if (!PacVisibility::canAddCourse($user)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Solo el administrador puede asignar unidad.',
                ], 403);
            }

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

            $cap = 'public.a2_acciones_capacitacion';
            [$schemaCap, $tableCap] = $this->splitQualified($cap);

            $colUnidad = $this->firstExistingColumn($schemaCap, $tableCap, ['unidad']);
            $colCoord  = $this->firstExistingColumn($schemaCap, $tableCap, ['coordinacion']);

            if (! $colUnidad || ! $colCoord) {
                return response()->json([
                    'status'  => false,
                    'message' => 'a2_acciones_capacitacion debe tener columnas: unidad y coordinacion.',
                ], 200);
            }

            $idCol = $this->firstExistingColumn($schemaCap, $tableCap, ['id']);
            if (! $idCol) {
                return response()->json([
                    'status'  => false,
                    'message' => 'a2_acciones_capacitacion no tiene columna id para actualizar.',
                ], 200);
            }

            $updated = DB::table($cap)
                ->where($idCol, (int) $validated['id'])
                ->update([
                    $colUnidad => (string) $unidadTxt,
                    $colCoord  => (string) $coordTxt,
                ]);

            if (! $updated) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No se actualizó el registro (verifica el ID).',
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