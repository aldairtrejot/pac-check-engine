<?php

namespace App\Models\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableActionModel extends Model
{
    /**
     * Retorna los registros de la tabla de acciones con paginación y búsqueda.
     *
     * @param  mixed  $limit
     * @param  mixed  $offset
     * @param  mixed  $search
     * @param  mixed  $select
     * @return array{allRow: int, list: \Illuminate\Support\Collection<int, \stdClass>, row: int}
     */
    public function list($limit, $offset, $search, $select)
    {
        $limit  = (int) $limit;
        $offset = (int) $offset;
        $select = (int) $select;
        $search = trim((string) $search);

        /*
         * Query para contar registros.
         */
        $countQuery = DB::table('public.a1_cat_acciones');

        $this->applySearch($countQuery, $search);

        $allRow = $countQuery->count();

        /*
         * Query para obtener datos.
         * Se usa id_accion AS id porque el Vue usa row.id.
         */
        $query = DB::table('public.a1_cat_acciones')
            ->selectRaw('
                public.a1_cat_acciones.id_accion AS id,
                public.a1_cat_acciones.estatus AS estatus,
                public.a1_cat_acciones.nombre_accion AS nombre_accion,
                public.a1_cat_acciones.tematica AS tematica
            ');

        $this->applySearch($query, $search);

        /*
         * Cantidad mostrada actualmente en la tabla.
         */
        $row = min($allRow, $offset + $limit);

        /*
         * Lista paginada.
         */
        $list = $query
            ->orderBy('public.a1_cat_acciones.id_accion', 'DESC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'row'    => $row,
            'allRow' => $allRow,
            'list'   => $list,
        ];
    }

    /**
     * Aplica filtros de búsqueda usando unaccent y comparación sin importar mayúsculas/minúsculas.
     *
     * @param  mixed  $query
     * @param  string $search
     * @return mixed
     */
    private function applySearch($query, string $search)
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function ($query) use ($search) {
            $query->whereRaw(
                'UPPER(TRIM(public.unaccent(public.a1_cat_acciones.estatus))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )
            ->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.a1_cat_acciones.nombre_accion))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )
            ->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.a1_cat_acciones.tematica))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            );
        });
    }
}