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
                $join->on(DB::raw('e.id_puesto::INTEGER'), '=', 'c.id_puesto')
                    ->whereRaw(
                        'UPPER(TRIM(public.unaccent(e.curp))) = UPPER(TRIM(public.unaccent(c.curp)))'
                    );
            });

        // Ocultar BAJA (3) en la lista: mostramos NULL, 1, 2
        $query->where(function ($q) {
            $q->whereNull('e.id_cat_estatus')
                ->orWhereIn('e.id_cat_estatus', [1, 2]);
        });

        /*
        |--------------------------------------------------------------------------
        | VISIBILIDAD
        |--------------------------------------------------------------------------
        | PacVisibility decide si el usuario ve todo o si se filtra.
        */
        PacVisibility::apply(
            $query,
            $user,
            'c',
            'public.a2_acciones_capacitacion'
        );

        $this->applySearch($query, $request);

        $countQuery = clone $query;
        $allRow = $countQuery->count();

        $list = $query
            ->orderBy('e.curp', 'ASC')
            ->offset((int) $offset)
            ->limit((int) $limit)
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
        if (! empty($request->name)) {
            $searchTerm = '%' . $request->name . '%';

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

        if (! empty($request->curp)) {
            $query->whereRaw(
                'UPPER(TRIM(public.unaccent(e.curp)))
                 LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $request->curp . '%']
            );
        }

        if (! empty($request->id_accion)) {
            $query->where('e.id_accion', '=', $request->id_accion);
        }

        return $query;
    }
}