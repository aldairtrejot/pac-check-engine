<?php

namespace App\Models\Pac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectionPacModel extends Model
{
    /**
     * The function returns the list of active roles for the combox
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('public.a1_cat_acciones')
            ->select(
                'public.a1_cat_acciones.id_accion AS id',
                'public.a1_cat_acciones.nombre_accion AS descripcion'
            )
            // ->where('ublic.a1_cat_acciones.estatus', '=', 'VIGENTE')
            ->orderBy('public.a1_cat_acciones.nombre_accion', 'ASC')
            ->get();
    }
}
