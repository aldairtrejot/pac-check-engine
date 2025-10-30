<?php

namespace App\Models\Pac\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CollectionInstanceModel extends Model
{
       /**
     * The function returns the list of active roles for the combox
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('public.cat_instancias')
            ->select(
                'public.cat_instancias.id_instancia as id',
                'public.cat_instancias.instancia AS descripcion'
            )
            ->orderBy('public.cat_instancias.instancia', 'ASC')
            ->get();
    }

    /**
     * Summary of listOptionsSelect
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listConllectionSelect($id)
    {
        return DB::table('public.cat_instancias')
            ->select(
                'public.cat_instancias.id_instancia as id',
                'public.cat_instancias.instancia AS descripcion'
            )
            ->where('public.cat_instancias.id_instancia', $id)
            ->get();
    }
}
