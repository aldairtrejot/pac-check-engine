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
         * ✅ Subconsulta: 1 registro “más reciente” por CURP desde ayo_ib_datos
         * (por si hay varias filas para el mismo CURP)
         */
        $ayoLast = DB::raw("
            (
                SELECT DISTINCT ON (UPPER(TRIM(curp)))
                    curp,
                    nombre_completo
                FROM public.ayo_ib_datos
                WHERE curp IS NOT NULL AND TRIM(curp) <> ''
                ORDER BY UPPER(TRIM(curp)),
                         anio DESC NULLS LAST,
                         no  DESC NULLS LAST
            ) as ayo
        ");

        $row = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                $ayoLast,
                DB::raw("UPPER(TRIM(ayo.curp))"),
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

                // ✅ Nombre desde ayo_ib_datos
                DB::raw("NULLIF(TRIM(ayo.nombre_completo), '') AS nombre_persona"),
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