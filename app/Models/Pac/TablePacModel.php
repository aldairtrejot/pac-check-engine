<?php

namespace App\Models\Pac;

use App\Support\PacVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TablePacModel extends Model
{
    public function list($limit, $offset, $search, $select, $request)
    {
        $query = DB::table('public.a2_acciones_empleados')
            ->selectRaw("
                public.a2_acciones_empleados.id_empl_accion AS id,
                public.a2_acciones_capacitacion.nombre AS nombre,
                public.a2_acciones_capacitacion.apellido_paterno || ' ' || 
                public.a2_acciones_capacitacion.apellido_materno AS apellido,
                public.a2_acciones_empleados.curp AS curp,
                public.a1_cat_acciones.nombre_accion AS accion,
                CASE 
                    WHEN (
                        public.a2_acciones_empleados.id_cat_estatus IS NOT NULL 
                        AND public.a2_acciones_empleados.fecha_ini IS NOT NULL
                        AND public.a2_acciones_empleados.fecha_fin IS NOT NULL
                        AND public.a2_acciones_empleados.id_trimestre IS NOT NULL
                        AND (
                            public.a2_acciones_empleados.id_instancia IS NOT NULL
                            AND TRIM(COALESCE(public.a2_acciones_empleados.id_instancia::text,'')) <> ''
                        )
                        AND (
                            public.a2_acciones_empleados.id_cat_tematica IS NOT NULL
                            AND TRIM(COALESCE(public.a2_acciones_empleados.id_cat_tematica::text,'')) <> ''
                        )
                    ) 
                    THEN 'CONCLUIDO'
                    ELSE 'PENDIENTE'
                END AS atendido
            ")
            ->join(
                'public.a1_cat_acciones',
                'public.a2_acciones_empleados.id_accion',
                '=',
                'public.a1_cat_acciones.id_accion'
            )
            ->join('public.a2_acciones_capacitacion', function ($join) {
                $join->on(
                    DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                    '=',
                    'public.a2_acciones_capacitacion.id_puesto'
                );
            });

        // ✅ VISIBILIDAD POR ROL/ENTIDAD/NÓMINA/CLUES (Laravel sí o sí)
        PacVisibility::apply($query, $request->user(), 'public.a2_acciones_capacitacion');

        // ✅ ocultar BAJA (3)
        $query->where(function ($q) {
            $q->whereNull('public.a2_acciones_empleados.id_cat_estatus')
              ->orWhereIn('public.a2_acciones_empleados.id_cat_estatus', [1, 2]);
        });

        // filtros (nombre/curp/acción)
        $this->applyFilters($query, $request);

        // Conteo total
        $countQuery = clone $query;
        $allRow = $countQuery->count();

        $list = $query->orderBy('public.a2_acciones_empleados.curp', 'ASC')
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

    private function applyFilters($query, $request)
    {
        // NAME (OR agrupado)
        if (! empty($request->name)) {
            $searchTerm = '%' . $request->name . '%';

            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.nombre))), ' ', '') 
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                )->orWhereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_paterno))), ' ', '') 
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                )->orWhereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_materno))), ' ', '') 
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                )->orWhereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.nombre))), ' ', '') || 
                     REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_paterno))), ' ', '') || 
                     REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_materno))), ' ', '') 
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                );
            });
        }

        // CURP
        if (! empty($request->curp)) {
            $query->whereRaw(
                'UPPER(TRIM(public.unaccent(public.a2_acciones_empleados.curp))) 
                 LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $request->curp . '%']
            );
        }

        // ACCIÓN
        if (! empty($request->id_accion)) {
            $query->where('public.a2_acciones_empleados.id_accion', '=', $request->id_accion);
        }

        return $query;
    }
}