<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Support\ConstanciaVisibilityByName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataConstanciasController extends Controller
{
    private const ESTATUS_PENDIENTE = 1;
    private const ESTATUS_ACEPTADA  = 2;
    private const ESTATUS_RECHAZADA = 3;

    public function data(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'status'  => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $id = trim((string) $request->input('id_respuesta', ''));

        if ($id === '') {
            return response()->json([
                'status'  => false,
                'message' => 'id_respuesta inválido.',
            ], 422);
        }

        $cols = $this->columnsFor('public', 'tbl_constancias');
        $colSet = array_flip($cols);

        $motivoColumn = $this->firstExistingColumnFromSet($colSet, [
            'motivo_rechazo',
            'motivo',
            'observaciones',
            'comentarios',
        ]);

        $fechaRechazoColumn = $this->firstExistingColumnFromSet($colSet, [
            'fecha_rechazo',
            'fecha_rechazo_at',
        ]);

        $capLast = DB::raw("
            (
                SELECT DISTINCT ON (UPPER(TRIM(curp)))
                    curp,
                    nombre,
                    apellido_paterno,
                    apellido_materno
                FROM public.a2_acciones_capacitacion
                WHERE curp IS NOT NULL AND TRIM(curp) <> ''
                ORDER BY UPPER(TRIM(curp)),
                         id_cat DESC NULLS LAST
            ) as cap
        ");

        $selects = [
            'c.*',

            DB::raw("
                CASE
                    WHEN c.estatus = " . self::ESTATUS_PENDIENTE . " THEN 'PENDIENTE'
                    WHEN c.estatus = " . self::ESTATUS_ACEPTADA  . " THEN 'ACEPTADO'
                    WHEN c.estatus = " . self::ESTATUS_RECHAZADA . " THEN 'RECHAZADO'
                    ELSE 'SIN ESTATUS'
                END AS estatus_txt
            "),

            DB::raw("COALESCE(NULLIF(c.hipervinculo,''), NULLIF(c.subir_constancia,'')) AS link_constancia"),

            DB::raw("
                NULLIF(
                    TRIM(CONCAT_WS(' ',
                        NULLIF(TRIM(cap.nombre), ''),
                        NULLIF(TRIM(cap.apellido_paterno), ''),
                        NULLIF(TRIM(cap.apellido_materno), '')
                    )),
                    ''
                ) AS nombre_persona
            "),
        ];

        if ($motivoColumn) {
            $selects[] = DB::raw("c.{$motivoColumn} AS motivo_rechazo_view");
        } else {
            $selects[] = DB::raw("NULL AS motivo_rechazo_view");
        }

        if ($fechaRechazoColumn) {
            $selects[] = DB::raw("CAST(c.{$fechaRechazoColumn} AS TEXT) AS fecha_rechazo_view");
        } else {
            $selects[] = DB::raw("NULL AS fecha_rechazo_view");
        }

        $row = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                $capLast,
                DB::raw("UPPER(TRIM(cap.curp))"),
                '=',
                DB::raw("UPPER(TRIM(c.curp))")
            )
            ->select($selects)
            ->where('c.id_respuesta', $id);

        ConstanciaVisibilityByName::apply($row, $user, 'c');

        $row = $row->first();

        if (! $row) {
            return response()->json([
                'status'  => false,
                'message' => 'Registro no encontrado o fuera de tu alcance.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $row,
        ]);
    }

    private function columnsFor(string $schema, string $table): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->orderBy('ordinal_position')
            ->pluck('column_name')
            ->map(fn ($c) => (string) $c)
            ->all();
    }

    private function firstExistingColumnFromSet(array $set, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (isset($set[$c])) {
                return $c;
            }
        }

        return null;
    }
}