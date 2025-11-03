<?php

namespace App\Models\Instancia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableInstanciaModel extends Model
{
    /**
     * Lista con paginación
     */
    public function list($limit, $offset, $search, $select)
    {
        $countQuery = DB::table('public.cat_instancias');
        $this->applySearch($countQuery, $search);
        $allRow = $countQuery->count();

        $query = DB::table('public.cat_instancias')
            ->selectRaw('
                public.cat_instancias.id_instancia AS id,
                public.cat_instancias.instancia AS instancia,
                public.cat_instancias.consecutivo AS consecutivo,
                public.cat_instancias.anio AS anio,
                public.cat_instancias.estatus AS estatus
            ');

        $this->applySearch($query, $search);

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        $list = $query->orderBy('public.cat_instancias.consecutivo', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'row'    => $row,
            'allRow' => $allRow,
            'list'   => $list,
        ];
    }

    private function applySearch($query, $search)
    {
        if ($search === null || $search === '') {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->whereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_instancias.id_instancia))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_instancias.instancia))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_instancias.estatus))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            );
        });
    }
}
