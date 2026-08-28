<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Support\ConstanciaVisibilityByName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableConstanciasController extends Controller
{
    private const ESTATUS_PENDIENTE = 1;
    private const ESTATUS_ACEPTADA  = 2;
    private const ESTATUS_RECHAZADA = 3;

    private const DEFAULT_LIMIT = 5;
    private const MAX_LIMIT     = 100;

    public function table(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'list'     => [],
                'allRow'   => 0,
                'row'      => 0,
                'status'   => false,
                'message'  => 'No autenticado.',
                'is_admin' => false,
            ], 401);
        }

        $scope = ConstanciaVisibilityByName::resolveScope((int) $user->id);
        $isAdmin = (bool) ($scope['is_admin_global'] ?? false);

        $request->validate([
            'limit'       => 'nullable|integer|min:1|max:' . self::MAX_LIMIT,
            'offset'      => 'nullable|integer|min:0',
            'curp'        => 'nullable|string|max:255',
            'curso'       => 'nullable|string|max:255',
            'anio'        => 'nullable',
            'estatus'     => 'nullable',
            'search'      => 'nullable|string|max:255',

            /*
            |--------------------------------------------------------------------------
            | Filtros solo para ADMIN
            |--------------------------------------------------------------------------
            | Aunque el frontend los oculta para no administradores, aquí también
            | se validan y solo se aplican cuando $isAdmin es true.
            */
            'entidad'     => 'nullable|string|max:255',
            'tipo_nomina' => 'nullable|string|max:255',
            'clues'       => 'nullable|string|max:255',
        ]);

        $limit  = (int) $request->input('limit', self::DEFAULT_LIMIT);
        $offset = (int) $request->input('offset', 0);

        $limit  = max(1, min($limit, self::MAX_LIMIT));
        $offset = max(0, $offset);

        $curp       = trim((string) $request->input('curp', ''));
        $curso      = trim((string) $request->input('curso', ''));
        $anioRaw    = trim((string) $request->input('anio', ''));
        $estatusRaw = trim((string) $request->input('estatus', ''));
        $search     = trim((string) $request->input('search', ''));

        $entidadFiltro    = trim((string) $request->input('entidad', ''));
        $tipoNominaFiltro = trim((string) $request->input('tipo_nomina', ''));
        $cluesFiltro      = trim((string) $request->input('clues', ''));

        if (! $isAdmin && ($entidadFiltro !== '' || $tipoNominaFiltro !== '' || $cluesFiltro !== '')) {
            return response()->json([
                'list'     => [],
                'allRow'   => 0,
                'row'      => 0,
                'status'   => false,
                'message'  => 'No tienes permisos para usar filtros administrativos.',
                'is_admin' => false,
            ], 403);
        }

        $anio    = ($anioRaw !== '' && is_numeric($anioRaw)) ? (int) $anioRaw : null;
        $estatus = ($estatusRaw !== '' && is_numeric($estatusRaw)) ? (int) $estatusRaw : null;

        $capLast = DB::raw("
            (
                SELECT DISTINCT ON (UPPER(TRIM(curp)))
                    curp,
                    nombre,
                    apellido_paterno,
                    apellido_materno
                FROM public.a2_acciones_capacitacion
                WHERE curp IS NOT NULL
                  AND TRIM(curp) <> ''
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

                /*
                |--------------------------------------------------------------------------
                | Columnas administrativas
                |--------------------------------------------------------------------------
                | Se devuelven para que el frontend las muestre únicamente si el
                | usuario es ADMIN. Para no administradores el backend también
                | mantiene su alcance con ConstanciaVisibilityByName.
                */
                'c.entidad',
                'c.tipo_nomina',
                'c.clues',

                /*
                |--------------------------------------------------------------------------
                | Calificación
                |--------------------------------------------------------------------------
                | La columna anterior era: c.calificacion.
                | La columna nueva es: c.calificacion_n.
                |
                | Se manda como "calificacion" para no romper el frontend si ya usa
                | row.calificacion, y también como "calificacion_n" por claridad.
                */
                DB::raw("c.calificacion_n AS calificacion"),
                DB::raw("c.calificacion_n AS calificacion_n"),

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
            ]);

        ConstanciaVisibilityByName::apply($q, $user, 'c');

        // No mostrar constancias inválidas para revisión:
        // - sin id_puesto
        // - sin estatus
        $q->whereNotNull('c.id_puesto')
          ->whereRaw("BTRIM(COALESCE(c.id_puesto::text, '')) <> ''")
          ->whereNotNull('c.estatus');

        if ($curp !== '') {
            $q->where('c.curp', 'ILIKE', "%{$curp}%");
        }

        if ($curso !== '') {
            $q->where('c.nombre_curso', 'ILIKE', "%{$curso}%");
        }

        if (! is_null($anio)) {
            $q->where('c.anio', $anio);
        }

        if (! is_null($estatus)) {
            $q->where('c.estatus', $estatus);
        }

        /*
        |--------------------------------------------------------------------------
        | Filtros administrativos
        |--------------------------------------------------------------------------
        | Solo se aplican si el usuario es ADMIN.
        | Para evitar capturas libres, el frontend los muestra como ComboBox.
        */
        if ($isAdmin) {
            if ($entidadFiltro !== '') {
                $q->whereRaw(
                    "UPPER(BTRIM(COALESCE(c.entidad::text, ''))) = ?",
                    [$this->norm($entidadFiltro)]
                );
            }

            if ($tipoNominaFiltro !== '') {
                $q->whereRaw(
                    "UPPER(BTRIM(COALESCE(c.tipo_nomina::text, ''))) = ?",
                    [$this->norm($tipoNominaFiltro)]
                );
            }

            if ($cluesFiltro !== '') {
                $q->whereRaw(
                    "UPPER(BTRIM(COALESCE(c.clues::text, ''))) = ?",
                    [$this->norm($cluesFiltro)]
                );
            }
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search, $isAdmin) {
                $w->where('c.curp', 'ILIKE', "%{$search}%")
                  ->orWhere('c.nombre_curso', 'ILIKE', "%{$search}%")
                  ->orWhereRaw("CAST(c.anio AS TEXT) ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CAST(c.estatus AS TEXT) ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("
                        TRIM(CONCAT_WS(' ',
                            NULLIF(TRIM(cap.nombre), ''),
                            NULLIF(TRIM(cap.apellido_paterno), ''),
                            NULLIF(TRIM(cap.apellido_materno), '')
                        )) ILIKE ?
                    ", ["%{$search}%"]);

                if ($isAdmin) {
                    $w->orWhere('c.entidad', 'ILIKE', "%{$search}%")
                      ->orWhere('c.tipo_nomina', 'ILIKE', "%{$search}%")
                      ->orWhere('c.clues', 'ILIKE', "%{$search}%");
                }
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
            'list'     => $list,
            'allRow'   => $allRow,
            'row'      => count($list),
            'is_admin' => $isAdmin,
        ]);
    }

    private function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
