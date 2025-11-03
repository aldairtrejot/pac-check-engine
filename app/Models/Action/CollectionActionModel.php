<?php

namespace App\Models\Action;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectionActionModel extends Model
{
    public function listTipoCapacitacion()
    {
        return DB::table('public.a1_cat_acciones')
            ->selectRaw('DISTINCT tipo_capacitacion AS descripcion')
            ->whereNotNull('tipo_capacitacion')
            ->whereRaw("TRIM(tipo_capacitacion) <> ''")
            ->orderBy('tipo_capacitacion', 'ASC')
            ->get();
    }

    public function listModalidades()
    {
        return DB::table('public.a1_cat_acciones')
            ->selectRaw('DISTINCT modalidad AS descripcion')
            ->whereNotNull('modalidad')
            ->whereRaw("TRIM(modalidad) <> ''")
            ->orderBy('modalidad', 'ASC')
            ->get();
    }

    // 🔹 NUEVO: RAMO
    public function listRamos()
    {
        return DB::table('public.a1_cat_acciones')
            ->selectRaw('DISTINCT ramo')
            ->whereNotNull('ramo')
            ->orderBy('ramo', 'ASC')
            ->get();
    }

    // 🔹 NUEVO: UR
    public function listURs()
    {
        return DB::table('public.a1_cat_acciones')
            ->selectRaw('DISTINCT ur AS descripcion')
            ->whereNotNull('ur')
            ->whereRaw("TRIM(ur) <> ''")
            ->orderBy('ur', 'ASC')
            ->get();
    }

    // 🔹 NUEVO: INSTITUCIÓN
    public function listInstituciones()
    {
        return DB::table('public.a1_cat_acciones')
            ->selectRaw('DISTINCT institucion AS descripcion')
            ->whereNotNull('institucion')
            ->whereRaw("TRIM(institucion) <> ''")
            ->orderBy('institucion', 'ASC')
            ->get();
    }
}
