<?php

namespace App\Models\Pac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectionPacModel extends Model
{
    /**
     * The function returns the list of active actions for the combo box.
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function listCollection()
    {
        return DB::table('public.a1_cat_acciones as a')
            ->select(
                'a.id_accion as id',
                'a.nombre_accion as descripcion'
            )
            ->where(function ($q) {
                $q->whereRaw("UPPER(TRIM(a.estatus)) = 'VIGENTE'")
                  ->orWhereRaw("UPPER(TRIM(a.estatus)) = 'ALTA'");
            })
            ->orderBy('a.nombre_accion', 'ASC')
            ->get();
    }
}