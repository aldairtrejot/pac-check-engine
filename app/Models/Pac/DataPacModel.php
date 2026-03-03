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
                DB::raw('e.id_empl_accion AS id'),

                // ===== Datos del empleado (CAP) =====
                DB::raw('cap.nivel_salarial AS nivel_salarial'),
                DB::raw('cap.rfc AS rfc'),
                DB::raw('cap.codigo_puesto AS codigo_puesto'),
                DB::raw('cap.puesto AS puesto'),
                DB::raw('cap.clave_clues AS clave_clues'),
                DB::raw("cap.nombre || ' ' || cap.apellido_paterno || ' ' || cap.apellido_materno AS nombre"),
                DB::raw('cap.entidad AS entidad'),
                DB::raw('cap.tipo_contratacion AS contratacion'),

                // ✅ Unidad/Coordinación (texto) desde catálogos
                DB::raw("u.nombre_unidad AS unidad"),
                DB::raw("co.nombre_coordinacion AS coordinacion"),

                // (por si los quieres usar luego)
                DB::raw("cap.id_unidad AS id_unidad"),
                DB::raw("cap.id_coordinacion AS id_coordinacion"),

                // ===== Datos PAC (empleados) =====
                DB::raw('e.curp AS curp'),
                DB::raw('a.nombre_accion AS accion'),
                DB::raw('e.fecha_ini AS fecha_ini'),
                DB::raw('e.fecha_fin AS fecha_fin'),
                DB::raw('e.observaciones AS observaciones'),
                DB::raw('e.id_cat_estatus AS id_cat_estatus'),
                DB::raw('e.id_instancia AS id_instancia'),
                DB::raw('e.id_cat_tematica AS id_cat_tematica'),
                DB::raw('e.id_finalidad AS id_finalidad'),
                DB::raw('e.eval_aprendizaje AS eval_aprendizaje'),
                DB::raw('a.duracion_hrs AS duracion_hrs'),
                DB::raw('e.horas_real AS horas_real'),
                DB::raw('a.tematica AS tematica_accion'),

                // ✅ calificación (si existe)
                DB::raw('e.calificacion AS calificacion'),
            ])
            ->join('public.a2_acciones_capacitacion as cap', function ($join) {
                $join->on(DB::raw('e.id_puesto::INTEGER'), '=', 'cap.id_puesto');
                // Si quieres amarrar también por curp (recomendado cuando hay duplicados por id_puesto)
                $join->on(DB::raw('UPPER(TRIM(e.curp))'), '=', DB::raw('UPPER(TRIM(cap.curp))'));
            })
            ->join('public.a1_cat_acciones as a', 'e.id_accion', '=', 'a.id_accion')
            ->leftJoin('public.cat_unidades as u', 'u.id_unidad', '=', 'cap.id_unidad')
            ->leftJoin('public.cat_coordinaciones as co', 'co.id_coordinacion', '=', 'cap.id_coordinacion')
            ->where('e.id_empl_accion', (int) $id);

        // ✅ filtro operativo (entidad/nomina/clues)
        PacVisibility::apply($query, $user, 'cap');

        return $query->first();
    }
}