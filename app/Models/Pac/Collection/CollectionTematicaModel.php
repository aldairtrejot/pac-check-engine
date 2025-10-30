<?php

namespace App\Models\Pac\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CollectionTematicaModel extends Model
{
           /**
     * The function returns the list of active roles for the combox
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('public.cat_tematica')
            ->select(
                'public.cat_tematica.id_tematica as id',
                'public.cat_tematica.tematica AS descripcion'
            )
            ->orderBy('public.cat_tematica.tematica', 'ASC')
            ->get();
    }

    /**
     * Summary of listOptionsSelect
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('public.cat_tematica')
            ->select(
                'public.cat_tematica.id_tematica as id',
                'public.cat_tematica.tematica AS descripcion'
            )
            ->where('public.cat_tematica.id_tematica', $id)
            ->get();
    }
}
