<?php

namespace App\Models\Tematica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableTematicaModel extends Model
{
    public function list($limit, $offset, $search, $select)
    {
        // COUNT
        $countQuery = DB::table('public.cat_tematica');
        $this->applySearch($countQuery, $search);
        $allRow = $countQuery->count();

        // DATA
        $query = DB::table('public.cat_tematica')
            ->selectRaw('
                public.cat_tematica.id_tematica AS id,
                public.cat_tematica.tematica   AS tematica,
                public.cat_tematica.categorias AS categorias,
                public.cat_tematica.enfoque    AS enfoque
            ');

        $this->applySearch($query, $search);

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        $list = $query->orderBy('public.cat_tematica.tematica', 'ASC')
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
        return $query->where(function ($q) use ($search) {
            $q->whereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_tematica.tematica))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_tematica.categorias))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_tematica.enfoque))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            )->orWhereRaw(
                'UPPER(TRIM(public.unaccent(public.cat_tematica.id_tematica))) LIKE UPPER(TRIM(public.unaccent(?)))',
                ['%' . $search . '%']
            );
        });
    }
}
