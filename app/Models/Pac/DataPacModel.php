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
                DB::raw("c.nombre || ' ' || c.apellido_paterno || ' ' || c.apellido_materno as nombre"),
                'c.entidad as entidad',
                'c.tipo_contratacion as contratacion',

                // ✅ ids reales (sí existen en tu tabla)
                'c.id_unidad as id_unidad',
                'c.id_coordinacion as id_coordinacion',

                // ✅ texto de unidad/coordinación (si existen catálogos)
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

                // ⚠️ Si tu tabla NO tiene calificacion, quita esta línea
                'e.calificacion as calificacion',
            ])
            ->join('public.a2_acciones_capacitacion as c', DB::raw('e.id_puesto::INTEGER'), '=', 'c.id_puesto')
            ->join('public.a1_cat_acciones as a', 'e.id_accion', '=', 'a.id_accion')

            // ✅ joins opcionales para texto (si no tienes estos catálogos, comenta estos 2 leftJoin)
            ->leftJoin('public.cat_unidades as cu', 'cu.id_unidad', '=', 'c.id_unidad')
            ->leftJoin('public.cat_coordinaciones as cc', 'cc.id_coordinacion', '=', 'c.id_coordinacion')

            ->where('e.id_empl_accion', $id);

        // ✅ filtro operativo (entidad/nomina/clues)
        PacVisibility::apply($query, $user, 'c');

        return $query->first();
    }
}