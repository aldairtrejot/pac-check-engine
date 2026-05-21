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

        /*
        |--------------------------------------------------------------------------
        | Validación básica del ID
        |--------------------------------------------------------------------------
        | Si viene vacío, nulo, texto o inválido, no se consulta nada.
        */
        if (! is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        $id = (int) $id;

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
                /*
                |--------------------------------------------------------------------------
                | JOIN seguro por puesto y CURP
                |--------------------------------------------------------------------------
                | e.id_puesto es TEXT y c.id_puesto es INTEGER.
                | Se castea únicamente si e.id_puesto contiene solo números.
                | Esto evita errores por valores vacíos, nulos o no numéricos.
                */
                $join->on(
                    DB::raw("
                        CASE
                            WHEN TRIM(e.id_puesto) ~ '^[0-9]+$'
                            THEN TRIM(e.id_puesto)::INTEGER
                            ELSE NULL
                        END
                    "),
                    '=',
                    'c.id_puesto'
                )
                ->whereRaw(
                    'UPPER(TRIM(public.unaccent(e.curp))) = UPPER(TRIM(public.unaccent(c.curp)))'
                );
            })
            ->join('public.a1_cat_acciones as a', 'e.id_accion', '=', 'a.id_accion')
            ->leftJoin('public.cat_unidades as cu', 'cu.id_unidad', '=', 'c.id_unidad')
            ->leftJoin('public.cat_coordinaciones as cc', 'cc.id_coordinacion', '=', 'c.id_coordinacion')
            ->where('e.id_empl_accion', $id);

        /*
        |--------------------------------------------------------------------------
        | Blindaje por estatus del registro PAC
        |--------------------------------------------------------------------------
        | Oculta registros dados de baja.
        | Solo permite:
        | - NULL
        | - 1
        | - 2
        */
        $query->where(function ($q) {
            $q->whereNull('e.id_cat_estatus')
                ->orWhereIn('e.id_cat_estatus', [1, 2]);
        });

        /*
        |--------------------------------------------------------------------------
        | Blindaje por activo del empleado
        |--------------------------------------------------------------------------
        | La columna activo pertenece a public.a2_acciones_capacitacion, alias c.
        | Solo permite:
        | - NULL
        | - 1
        | - 2
        |
        | Oculta:
        | - 0
        | - 3
        | - cualquier otro valor
        */
        $query->where(function ($q) {
            $q->whereNull('c.activo')
                ->orWhereIn('c.activo', [1, 2]);
        });

        /*
        |--------------------------------------------------------------------------
        | VISIBILIDAD POR ALCANCE
        |--------------------------------------------------------------------------
        | No se valida manualmente si es admin/revisor/supervisor.
        | PacVisibility decide:
        | - ADMIN_OC / ADMIN ve todo.
        | - SUPERVISOR_OC / REVISOR_EST / SUPERVISOR_EST se filtran.
        | - Usuarios sin alcance válido no deben ver información.
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