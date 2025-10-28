<?php

namespace App\Models\Pac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TablePacModel extends Model
{
    /**
     * The function returns the table apart from its query, however it expects the limit, offset,
     * search and select as parameters, returning the generated query, the total and the iterator.
     *
     * @param  mixed  $limit
     * @param  mixed  $offset
     * @param  mixed  $search
     * @param  mixed  $select
     * @return array{allRow: int, list: \Illuminate\Support\Collection<int, \stdClass>, row: float|int}
     */
    public function list($limit, $offset, $search, $select, $request)
    {
        // Primero crea la query base con todos los JOINs
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
                    OR public.a2_acciones_empleados.fecha_ini IS NOT NULL
                    OR public.a2_acciones_empleados.fecha_fin IS NOT NULL
                    OR public.a2_acciones_empleados.id_trimestre IS NOT NULL
                    OR public.a2_acciones_empleados.id_instancia IS NOT NULL
                    OR public.a2_acciones_empleados.id_cat_tematica IS NOT NULL
                ) 
                THEN 'CONCLUIDO'
                ELSE 'PENDIENTE'
            END AS atendido
        ")
            ->join('public.a1_cat_acciones', 'public.a2_acciones_empleados.id_accion', '=', 'public.a1_cat_acciones.id_accion')
            ->join('public.a2_acciones_capacitacion', function ($join) {
                $join->on(DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'), '=', 'public.a2_acciones_capacitacion.id_puesto');
            });

        // Aplica los filtros de búsqueda
        $this->applySearch($query, $request);

        // Clona la query para el conteo (antes de limit/offset)
        $countQuery = clone $query;
        $allRow = $countQuery->count();

        // Aplica ordenamiento y paginación a la query original
        $list = $query->orderBy('public.a2_acciones_empleados.curp', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        return [
            'row' => $row,
            'allRow' => $allRow,
            'list' => $list,
        ];
    }

    /**
     * Private helper to apply the search filters using unaccent and case-insensitive comparison
     *
     * @param  mixed  $query
     * @param  mixed  $search
     */
    private function applySearch($query, $request)
    {
        return $query->where(function ($query) use ($request) {

            if (! empty($request->name)) {
                $query->whereRaw(
                    'UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.nombre))) LIKE UPPER(TRIM(public.unaccent(?)))',
                    ['%'.$request->name.'%']
                )->orWhereRaw(
                    'UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_paterno))) LIKE UPPER(TRIM(public.unaccent(?)))',
                    ['%'.$request->name.'%']
                )->orWhereRaw(
                    'UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_materno))) LIKE UPPER(TRIM(public.unaccent(?)))',
                    ['%'.$request->name.'%']
                )->orWhereRaw(
                    "UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.nombre))) || '  ' ||
                UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_paterno))) || ' ' ||
                UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_materno))) LIKE UPPER(TRIM(public.unaccent(?)))",
                    ['%'.$request->name.'%']
                );
            }

            // Filtro adicional para CURP
            if (! empty($request->curp)) {
                $query->where('public.a2_acciones_empleados.curp', 'LIKE', '%'.$request->curp.'%');
            }

            if (! empty($request->id_accion)) {
                $query->where(
                    'public.a2_acciones_empleados.id_accion', '=', $request->id_accion
                );
            }

            // Filtro adicional para estado (COMPLETO/INCOMPLETO)
            // Filtro adicional para estado (COMPLETO/INCOMPLETO)
            /*
            if (isset($request->is_complete)) {
                if ($request->is_complete === '1') {
                    // COMPLETO: NINGUNO puede ser NULL (todos deben tener datos)
                    $query->whereNotNull('public.a2_acciones_empleados.id_cat_estatus')
                        ->whereNotNull('public.a2_acciones_empleados.fecha_ini')
                        ->whereNotNull('public.a2_acciones_empleados.fecha_fin')
                        ->whereNotNull('public.a2_acciones_empleados.id_trimestre')
                        ->whereNotNull('public.a2_acciones_empleados.id_instancia')
                        ->whereNotNull('public.a2_acciones_empleados.id_cat_tematica');
                } elseif ($request->is_complete === '0') {
                    // INCOMPLETO: AL MENOS UNO puede ser NULL
                    $query->where(function ($q) {
                        $q->whereNull('public.a2_acciones_empleados.id_cat_estatus')
                            ->orWhereNull('public.a2_acciones_empleados.fecha_ini')
                            ->orWhereNull('public.a2_acciones_empleados.fecha_fin')
                            ->orWhereNull('public.a2_acciones_empleados.id_trimestre')
                            ->orWhereNull('public.a2_acciones_empleados.id_instancia')
                            ->orWhereNull('public.a2_acciones_empleados.id_cat_tematica');
                    });
                }
            }*/

        });
    }
}
