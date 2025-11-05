<?php

namespace App\Models\Pac\Collection;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectionFinalidadModel extends Model
{
    /**
     * Catálogo completo para combos.
     */
    public function listCollection()
    {
        return DB::table('public.cat_finalidad')
            ->select(
                'id_finalidad as id',
                'desc_finalidad as descripcion'
            )
            ->orderBy('id_finalidad', 'ASC')
            ->get();
    }

    /**
     * Opción seleccionada por id.
     */
    public function listConllectionSelect($id)
    {
        return DB::table('public.cat_finalidad')
            ->select(
                'id_finalidad as id',
                'desc_finalidad as descripcion'
            )
            ->where('id_finalidad', $id)
            ->get();
    }
}
