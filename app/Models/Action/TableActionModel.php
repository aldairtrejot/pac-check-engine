<?php

namespace App\Models\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableActionModel extends Model
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
        public function list($limit, $offset, $search, $select)
    {
        // Query para contar
        $countQuery = DB::table('public.a1_cat_acciones');
        $this->applySearch($countQuery, $search);
        $allRow = $countQuery->count();

        // Query para datos
        $query = DB::table('public.a1_cat_acciones')
            ->selectRaw('
                public.a1_cat_acciones.id_accion AS id,
                public.a1_cat_acciones.estatus AS estatus,
                public.a1_cat_acciones.nombre_accion AS nombre_accion,
                public.a1_cat_acciones.tematica AS tematica
            ');

        $this->applySearch($query, $search);

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        $list = $query->orderBy('public.a1_cat_acciones.id_accion', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'row' => $row,
            'allRow' => $allRow,
            'list'  => $list,
        ];
    }
    /**
     * Private helper to apply the search filters using unaccent and case-insensitive comparison
     *
     * @param  mixed  $query
     * @param  mixed  $search
     */
    private function applySearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->whereRaw(
                'UPPER(TRIM(public.unaccent(public.a1_cat_acciones.estatus))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.a1_cat_acciones.nombre_accion))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.a1_cat_acciones.tematica))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            );
        });
    }
}
