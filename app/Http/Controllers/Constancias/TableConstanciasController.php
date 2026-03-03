<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableConstanciasController extends Controller
{
    private const ESTATUS_PENDIENTE = 1;
    private const ESTATUS_ACEPTADA  = 2;
    private const ESTATUS_RECHAZADA = 3;

    public function table(Request $request)
    {
        $limit   = (int) ($request->input('limit', 5));
        $offset  = (int) ($request->input('offset', 0));

        $curp    = trim((string) $request->input('curp', ''));
        $curso   = trim((string) $request->input('curso', ''));
        $anioRaw = trim((string) $request->input('anio', ''));
        $search  = trim((string) $request->input('search', ''));

        $anio = ($anioRaw !== '' && is_numeric($anioRaw)) ? (int) $anioRaw : null;

        // ✅ Subconsulta 1 por CURP para evitar duplicados
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

        $q = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                $capLast,
                DB::raw("UPPER(TRIM(cap.curp))"),
                '=',
                DB::raw("UPPER(TRIM(c.curp))")
            )
            ->select([
                'c.id_respuesta',
                'c.curp',
                'c.nombre_curso',
                'c.anio',
                'c.estatus',

                DB::raw("
                    CASE
                        WHEN c.estatus = " . self::ESTATUS_PENDIENTE . " THEN 'PENDIENTE'
                        WHEN c.estatus = " . self::ESTATUS_ACEPTADA  . " THEN 'ACEPTADA'
                        WHEN c.estatus = " . self::ESTATUS_RECHAZADA . " THEN 'RECHAZADA'
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
            ]);

        if ($curp !== '') {
            $q->where('c.curp', 'ILIKE', "%{$curp}%");
        }
        if ($curso !== '') {
            $q->where('c.nombre_curso', 'ILIKE', "%{$curso}%");
        }
        if (!is_null($anio)) {
            $q->where('c.anio', $anio);
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('c.curp', 'ILIKE', "%{$search}%")
                  ->orWhere('c.nombre_curso', 'ILIKE', "%{$search}%")
                  ->orWhereRaw("CAST(c.anio AS TEXT) ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CAST(c.estatus AS TEXT) ILIKE ?", ["%{$search}%"]);
            });
        }

        $allRow = (clone $q)->count();

        $list = $q->orderByDesc('c.fecha_ultima_accion')
            ->orderByDesc('c.fecha_ini_accion')
            ->orderByDesc('c.fecha_envio')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'list'   => $list,
            'allRow' => $allRow,
            'row'    => count($list),
        ]);
    }
}