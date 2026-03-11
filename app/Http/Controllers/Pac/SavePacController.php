<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\EntityPacModel;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavePacController extends Controller
{
    public function save(Request $request)
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'id' => 'required|integer',

                // del form
                'm_horas_real' => 'nullable|numeric',
                'm_fecha_ini'  => 'nullable|date',
                'm_fecha_fin'  => 'nullable|date',
                'm_observaciones' => 'nullable|string|max:1000',
                'm_eval_aprendizaje' => 'nullable|in:0,1',

                // selects (vienen por append desde Vue)
                'id_cat_estatus'  => 'nullable|integer',
                'id_instancia'    => 'nullable|string|max:50',
                'id_cat_tematica' => 'nullable|string|max:50',
                'id_finalidad'    => 'nullable|integer',

                // ✅ NUEVO
                'calificacion'    => 'nullable|integer|min:70|max:100',
            ]);

            // ✅ chequeo de acceso correcto: id_puesto + curp
            $allowed = DB::table('public.a2_acciones_empleados')
                ->join('public.a2_acciones_capacitacion', function ($join) {
                    $join->on(
                        DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                        '=',
                        'public.a2_acciones_capacitacion.id_puesto'
                    )->whereRaw(
                        'UPPER(TRIM(public.unaccent(public.a2_acciones_empleados.curp))) = UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.curp)))'
                    );
                })
                ->where('public.a2_acciones_empleados.id_empl_accion', (int) $validated['id']);

            PacVisibility::apply($allowed, $user, 'public.a2_acciones_capacitacion');

            if (! $allowed->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Acceso denegado o registro no encontrado.',
                ], 200);
            }

            $row = EntityPacModel::findOrFail((int) $validated['id']);

            // Normalizar vacíos a null
            $idEstatus   = ($validated['id_cat_estatus'] ?? '') !== '' ? (int) $validated['id_cat_estatus'] : null;
            $idFinalidad = ($validated['id_finalidad'] ?? '') !== '' ? (int) $validated['id_finalidad'] : null;

            $idInstancia = trim((string)($validated['id_instancia'] ?? ''));
            $idInstancia = $idInstancia !== '' ? $idInstancia : null;

            $idTematica = trim((string)($validated['id_cat_tematica'] ?? ''));
            $idTematica = $idTematica !== '' ? $idTematica : null;

            $fechaIni = $validated['m_fecha_ini'] ?? null;
            $fechaFin = $validated['m_fecha_fin'] ?? null;

            // trimestre automático por fecha_ini
            $idTrimestre = $row->id_trimestre;
            if (! empty($fechaIni)) {
                $m = (int) date('n', strtotime($fechaIni));
                $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
            }

            // horas_real: si viene vacío -> usar duración del curso
            $horasReal = $validated['m_horas_real'] ?? null;
            if ($horasReal === null || $horasReal === '') {
                $dur = DB::table('public.a1_cat_acciones')
                    ->where('id_accion', $row->id_accion)
                    ->value('duracion_hrs');
                $horasReal = $dur !== null ? (float) $dur : null;
            } else {
                $horasReal = (float) $horasReal;
            }

            $obs = $validated['m_observaciones'] ?? null;
            $obs = $obs !== null ? mb_strtoupper(trim($obs)) : null;

            $eval = ($validated['m_eval_aprendizaje'] ?? '0') === '1' ? 1 : 0;

            // ✅ calificación: default 100, entero, clamp 70..100
            $cal = $validated['calificacion'] ?? 100;
            $cal = (int) $cal;
            if ($cal < 70) $cal = 70;
            if ($cal > 100) $cal = 100;

            $row->id_cat_estatus   = $idEstatus;
            $row->id_finalidad     = $idFinalidad;
            $row->id_instancia     = $idInstancia;
            $row->id_cat_tematica  = $idTematica;
            $row->fecha_ini        = $fechaIni;
            $row->fecha_fin        = $fechaFin;
            $row->id_trimestre     = $idTrimestre;
            $row->horas_real       = $horasReal;
            $row->observaciones    = $obs;
            $row->eval_aprendizaje = $eval;
            $row->calificacion     = $cal;

            $row->save();

            return response()->json([
                'status' => true,
                'message' => 'Registro actualizado correctamente.',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }
}