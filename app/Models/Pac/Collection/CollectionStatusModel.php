<?php

namespace App\Models\Pac\Collection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectionStatusModel extends Model
{
    /**
     * The function returns the list of active roles for the combox
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection($id)
    {
        return DB::table('public.cat_estatus')
            ->select(
                'public.cat_estatus.id_cat_estatus as id',
                'public.cat_estatus.descripcion as descripcion'
            )
            ->orderBy('public.cat_coordinacion.descripcion', 'ASC')
            ->get();
    }

    /**
     * Summary of listOptionsSelect
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('public.cat_estatus')
            ->select(
                'public.cat_estatus.id_cat_estatus as id',
                'public.cat_estatus.descripcion as descripcion'
            )
            ->where('public.cat_estatus.id_cat_estatus', $id)
            ->get();
    }
}
