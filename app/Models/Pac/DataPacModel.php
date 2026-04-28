<?php

namespace App\Models\Pac;

use App\Support\PacVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataPacModel extends Model
{
    public function dataPac($id)
    {
        $user = auth()->user();

        $query = DB::table('public.a2_acciones_empleados as e')
            ->select([
                'e.id_empl_accion as id',

                'c.nivel_salarial as nivel_salarial',
                'c.rfc as rfc',
                'c.codigo_puesto as codigo_puesto',
                'c.puesto as puesto',
                'c.clave_clues as clave_clues',
                DB::raw("TRIM(CONCAT_WS(' ', c.nombre, c.apellido_paterno, c.apellido_materno)) as nombre"),
                'c.entidad as entidad',
                'c.tipo_contratacion as contratacion',

                'c.id_unidad as id_unidad',
                'c.id_coordinacion as id_coordinacion',

                DB::raw("cu.nombre_unidad as unidad"),
                DB::raw("cc.nombre_coordinacion as coordinacion"),

                'e.curp as curp',
                'a.nombre_accion as accion',
                'e.fecha_ini as fecha_ini',
                'e.fecha_fin as fecha_fin',
                'e.observaciones as observaciones',
                'e.id_cat_estatus as id_cat_estatus',
                'e.id_instancia as id_instancia',
                'e.id_cat_tematica as id_cat_tematica',
                'e.id_finalidad as id_finalidad',
                'e.eval_aprendizaje as eval_aprendizaje',
                'a.duracion_hrs as duracion_hrs',
                'e.horas_real as horas_real',
                'a.tematica as tematica_accion',
                'e.calificacion as calificacion',
            ])
            ->join('public.a2_acciones_capacitacion as c', function ($join) {
                $join->on(DB::raw('e.id_puesto::INTEGER'), '=', 'c.id_puesto')
                    ->whereRaw(
                        'UPPER(TRIM(public.unaccent(e.curp))) = UPPER(TRIM(public.unaccent(c.curp)))'
                    );
            })
            ->join('public.a1_cat_acciones as a', 'e.id_accion', '=', 'a.id_accion')
            ->leftJoin('public.cat_unidades as cu', 'cu.id_unidad', '=', 'c.id_unidad')
            ->leftJoin('public.cat_coordinaciones as cc', 'cc.id_coordinacion', '=', 'c.id_coordinacion')
            ->where('e.id_empl_accion', (int) $id);

        /*
        |--------------------------------------------------------------------------
        | VISIBILIDAD
        |--------------------------------------------------------------------------
        | No se valida aquí si es admin/revisor.
        | PacVisibility decide:
        | - ADMIN_OC / ADMIN ve todo.
        | - SUPERVISOR_OC / REVISOR_EST / SUPERVISOR_EST se filtran.
        */
        PacVisibility::apply(
            $query,
            $user,
            'c',
            'public.a2_acciones_capacitacion'
        );

        return $query->first();
    }
}