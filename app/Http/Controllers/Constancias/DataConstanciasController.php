<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataConstanciasController extends Controller
{
    private const ESTATUS_PENDIENTE = 1;
    private const ESTATUS_ACEPTADA  = 2;
    private const ESTATUS_RECHAZADA = 3;

    public function data(Request $request)
    {
        $id = trim((string) $request->input('id_respuesta', ''));

        if ($id === '') {
            return response()->json([
                'status' => false,
                'message' => 'id_respuesta inválido.',
            ], 422);
        }

        /**
         * ✅ Subconsulta: 1 registro por CURP desde a2_acciones_capacitacion
         * (evita duplicados si hay múltiples filas por la misma CURP)
         * Aquí elegimos el "más reciente" por id_cat DESC (ajustable si tienes otra lógica)
         */
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

        $row = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                $capLast,
                DB::raw("UPPER(TRIM(cap.curp))"),
                '=',
                DB::raw("UPPER(TRIM(c.curp))")
            )
            ->select([
                'c.*',

                DB::raw("
                    CASE
                        WHEN c.estatus = " . self::ESTATUS_PENDIENTE . " THEN 'PENDIENTE'
                        WHEN c.estatus = " . self::ESTATUS_ACEPTADA  . " THEN 'ACEPTADA'
                        WHEN c.estatus = " . self::ESTATUS_RECHAZADA . " THEN 'RECHAZADA'
                        ELSE 'SIN ESTATUS'
                    END AS estatus_txt
                "),

                DB::raw("COALESCE(NULLIF(c.hipervinculo,''), NULLIF(c.subir_constancia,'')) AS link_constancia"),

                // ✅ Nombre desde a2_acciones_capacitacion
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
            ])
            ->where('c.id_respuesta', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'status' => false,
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $row,
        ]);
    }
}