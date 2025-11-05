<?php

namespace App\Models\Pac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataPacModel extends Model
{
    /**
     * La función lista los datos principales de las tablas
     *
     * @param  mixed  $id
     * @return \stdClass|null
     */
    public function dataPac($id)
    {
        $query = DB::table('public.a2_acciones_empleados')
            ->select(
                'public.a2_acciones_empleados.id_empl_accion AS id',
                'public.a2_acciones_capacitacion.nivel_salarial AS nivel_salarial',
                'public.a2_acciones_capacitacion.rfc AS rfc',
                'public.a2_acciones_capacitacion.codigo_puesto AS codigo_puesto',
                'public.a2_acciones_capacitacion.puesto AS puesto',
                'public.a2_acciones_capacitacion.clave_clues AS clave_clues',
                DB::raw("public.a2_acciones_capacitacion.nombre || ' ' || public.a2_acciones_capacitacion.apellido_paterno || ' ' || public.a2_acciones_capacitacion.apellido_materno AS nombre"),
                'public.a2_acciones_capacitacion.entidad AS entidad',
                'public.a2_acciones_capacitacion.tipo_contratacion AS contratacion',
                'public.a2_acciones_empleados.curp AS curp',
                'public.a1_cat_acciones.nombre_accion AS accion',
                'public.a2_acciones_empleados.fecha_ini AS fecha_ini',
                'public.a2_acciones_empleados.fecha_fin AS fecha_fin',
                'public.a2_acciones_empleados.observaciones AS observaciones',
                'public.a2_acciones_empleados.id_cat_estatus AS id_cat_estatus',
                'public.a2_acciones_empleados.id_instancia AS id_instancia',
                'public.a2_acciones_empleados.id_cat_tematica AS id_cat_tematica',
                'public.a2_acciones_empleados.id_finalidad AS id_finalidad', // 🔹 NUEVO: finalidad del empleado-curso
                'public.a2_acciones_empleados.eval_aprendizaje AS eval_aprendizaje',
                'public.a1_cat_acciones.duracion_hrs AS duracion_hrs',
                'public.a2_acciones_empleados.horas_real AS horas_real',
                // 🔹 Temática del curso en el catálogo de acciones
                'public.a1_cat_acciones.tematica AS tematica_accion'
            )
            ->join(
                'public.a2_acciones_capacitacion',
                DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                '=',
                'public.a2_acciones_capacitacion.id_puesto'
            )->join(
                'public.a1_cat_acciones',
                'public.a2_acciones_empleados.id_accion',
                '=',
                'public.a1_cat_acciones.id_accion'
            )
            ->where('public.a2_acciones_empleados.id_empl_accion', $id)
            ->first();

        return $query;
    }
}
