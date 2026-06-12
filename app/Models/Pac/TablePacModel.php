<?php

namespace App\Models\Pac;

use App\Support\PacVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TablePacModel extends Model
{
    public function list($limit, $offset, $search, $select, $request)
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Paginación segura
        |--------------------------------------------------------------------------
        | Evita límites demasiado grandes o valores negativos.
        | Máximo 50 registros por consulta para no exponer/cargar información de más.
        */
        $limit  = max(1, min((int) $limit, 50));
        $offset = max(0, (int) $offset);
        $select = max(1, min((int) $select, 50));

        $query = DB::table('public.a2_acciones_empleados as e')
            ->selectRaw("
                e.id_empl_accion AS id,
                c.nombre AS nombre,
                TRIM(CONCAT_WS(' ', c.apellido_paterno, c.apellido_materno)) AS apellido,
                e.curp AS curp,
                a.nombre_accion AS accion,
                CASE
                    WHEN (
                        e.id_cat_estatus IS NOT NULL
                        AND e.fecha_ini IS NOT NULL
                        AND e.fecha_fin IS NOT NULL
                        AND e.id_trimestre IS NOT NULL
                        AND (
                            e.id_instancia IS NOT NULL
                            AND TRIM(e.id_instancia) <> ''
                        )
                        AND (
                            e.id_cat_tematica IS NOT NULL
                            AND TRIM(e.id_cat_tematica) <> ''
                        )
                    )
                    THEN 'CONCLUIDO'
                    ELSE 'PENDIENTE'
                END AS atendido
            ")
            ->join('public.a1_cat_acciones as a', 'e.id_accion', '=', 'a.id_accion')
            ->join('public.a2_acciones_capacitacion as c', function ($join) {
                /*
                |--------------------------------------------------------------------------
                | JOIN seguro por puesto y CURP
                |--------------------------------------------------------------------------
                | e.id_puesto es TEXT y c.id_puesto es INTEGER.
                | Se usa CASE para castear solo cuando e.id_puesto sea numérico.
                | Esto evita errores si llega un id_puesto vacío, nulo o con letras.
                */
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
                )
                ->whereRaw(
                    'UPPER(TRIM(public.unaccent(e.curp))) = UPPER(TRIM(public.unaccent(c.curp)))'
                );
            });

        /*
        |--------------------------------------------------------------------------
        | FILTRO PRINCIPAL: estatus del catálogo de acciones/cursos
        |--------------------------------------------------------------------------
        | Este es el filtro que faltaba.
        |
        | Si una acción/curso en public.a1_cat_acciones está como NO VIGENTE,
        | ya no se muestra en "Mi plantilla", aunque siga asociada al empleado
        | en public.a2_acciones_empleados.
        */
        $query->whereRaw("TRIM(UPPER(COALESCE(a.estatus, ''))) = 'VIGENTE'");

        /*
        |--------------------------------------------------------------------------
        | Filtro de estatus del registro PAC del empleado
        |--------------------------------------------------------------------------
        | Oculta BAJA y NO VIGENTE del registro asignado al empleado.
        |
        | Solo muestra registros con:
        | - NULL = pendiente sin atender
        | - 1    = VIGENTE
        | - 2    = ALTA
        |
        | Oculta:
        | - 3 = BAJA
        | - 4 = NO VIGENTE
        |
        | IMPORTANTE:
        | No usar solamente e.id_cat_estatus = 1 porque eso ocultaría cursos
        | pendientes que todavía vienen con id_cat_estatus NULL.
        */
        $query->where(function ($q) {
            $q->whereNull('e.id_cat_estatus')
                ->orWhereIn('e.id_cat_estatus', [1, 2]);
        });

        /*
        |--------------------------------------------------------------------------
        | Filtro de activo del empleado en capacitación
        |--------------------------------------------------------------------------
        | La columna activo pertenece a public.a2_acciones_capacitacion, alias c.
        | Solo muestra:
        | - NULL
        | - 1
        | - 2
        |
        | Oculta:
        | - 0
        | - 3
        | - cualquier otro valor
        */
        $query->where(function ($q) {
            $q->whereNull('c.activo')
                ->orWhereIn('c.activo', [1, 2]);
        });

        /*
        |--------------------------------------------------------------------------
        | VISIBILIDAD POR ALCANCE
        |--------------------------------------------------------------------------
        | PacVisibility decide si el usuario ve todo o si se filtra por alcance.
        | Este filtro debe permanecer para evitar que usuarios sin permiso vean datos
        | fuera de su entidad, nómina, unidad, coordinación, etc.
        */
        PacVisibility::apply(
            $query,
            $user,
            'c',
            'public.a2_acciones_capacitacion'
        );

        /*
        |--------------------------------------------------------------------------
        | Filtros de búsqueda
        |--------------------------------------------------------------------------
        */
        $this->applySearch($query, $request);

        /*
        |--------------------------------------------------------------------------
        | Conteo total ya con filtros, activo y visibilidad aplicados
        |--------------------------------------------------------------------------
        */
        $countQuery = clone $query;
        $allRow = $countQuery->count();

        /*
        |--------------------------------------------------------------------------
        | Listado paginado
        |--------------------------------------------------------------------------
        */
        $list = $query
            ->orderBy('e.curp', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        return [
            'row'    => $row,
            'allRow' => $allRow,
            'list'   => $list,
        ];
    }

    private function applySearch($query, $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Búsqueda por nombre
        |--------------------------------------------------------------------------
        | Busca por nombre, apellido paterno, apellido materno y nombre completo.
        | Ignora acentos, mayúsculas/minúsculas y espacios.
        */
        if (! empty($request->name)) {
            $name = trim((string) $request->name);

            if ($name !== '') {
                $searchTerm = '%' . $name . '%';

                $query->where(function ($q) use ($searchTerm) {
                    $q->whereRaw(
                        "REPLACE(UPPER(TRIM(public.unaccent(c.nombre))), ' ', '')
                         LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                        [$searchTerm]
                    )
                    ->orWhereRaw(
                        "REPLACE(UPPER(TRIM(public.unaccent(c.apellido_paterno))), ' ', '')
                         LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                        [$searchTerm]
                    )
                    ->orWhereRaw(
                        "REPLACE(UPPER(TRIM(public.unaccent(c.apellido_materno))), ' ', '')
                         LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                        [$searchTerm]
                    )
                    ->orWhereRaw(
                        "REPLACE(UPPER(TRIM(public.unaccent(c.nombre))), ' ', '') ||
                         REPLACE(UPPER(TRIM(public.unaccent(c.apellido_paterno))), ' ', '') ||
                         REPLACE(UPPER(TRIM(public.unaccent(c.apellido_materno))), ' ', '')
                         LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                        [$searchTerm]
                    );
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Búsqueda por CURP
        |--------------------------------------------------------------------------
        */
        if (! empty($request->curp)) {
            $curp = trim((string) $request->curp);

            if ($curp !== '') {
                $query->whereRaw(
                    'UPPER(TRIM(public.unaccent(e.curp)))
                     LIKE UPPER(TRIM(public.unaccent(?)))',
                    ['%' . $curp . '%']
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Filtro por acción
        |--------------------------------------------------------------------------
        | Si id_accion viene inválido, no se deja pasar la consulta abierta.
        | Se fuerza a no devolver resultados para evitar mostrar información de más.
        */
        if (! empty($request->id_accion)) {
            if (is_numeric($request->id_accion)) {
                $query->where('e.id_accion', '=', (int) $request->id_accion);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }
}