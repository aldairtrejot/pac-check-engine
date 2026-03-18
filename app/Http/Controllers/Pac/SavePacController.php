<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\EntityPacModel;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SavePacController extends Controller
{
    public function save(Request $request)
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No autenticado.',
                ], 401);
            }

            $validated = $request->validate([
                'id' => 'required|integer',

                // del form
                'm_horas_real'        => 'nullable|numeric',
                'm_fecha_ini'         => 'nullable|date',
                'm_fecha_fin'         => 'nullable|date',
                'm_observaciones'     => 'nullable|string|max:1000',
                'm_eval_aprendizaje'  => 'nullable|in:0,1',

                // selects
                'id_cat_estatus'      => 'nullable|integer',
                'id_instancia'        => 'nullable|string|max:50',
                'id_cat_tematica'     => 'nullable|string|max:50',
                'id_finalidad'        => 'nullable|integer',

                // calificación
                'calificacion'        => 'nullable|integer|min:70|max:100',
            ]);

            $id = (int) $validated['id'];

            /**
             * Para admin/admin_oc/revisor_est:
             * - acceso total al guardado en este módulo
             *
             * Para otros roles:
             * - se mantiene PacVisibility
             */
            if ($this->isAdminOrRevisorEst($user)) {
                $allowed = DB::table('public.a2_acciones_empleados')
                    ->where('id_empl_accion', $id);
            } else {
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
                    ->where('public.a2_acciones_empleados.id_empl_accion', $id);

                PacVisibility::apply($allowed, $user, 'public.a2_acciones_capacitacion');
            }

            if (! $allowed->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Acceso denegado o registro no encontrado.',
                ], 200);
            }

            $row = EntityPacModel::findOrFail($id);

            $idEstatus   = ($validated['id_cat_estatus'] ?? '') !== '' ? (int) $validated['id_cat_estatus'] : null;
            $idFinalidad = ($validated['id_finalidad'] ?? '') !== '' ? (int) $validated['id_finalidad'] : null;

            $idInstancia = trim((string) ($validated['id_instancia'] ?? ''));
            $idInstancia = $idInstancia !== '' ? $idInstancia : null;

            $idTematica = trim((string) ($validated['id_cat_tematica'] ?? ''));
            $idTematica = $idTematica !== '' ? $idTematica : null;

            $fechaIni = $validated['m_fecha_ini'] ?? null;
            $fechaFin = $validated['m_fecha_fin'] ?? null;

            $idTrimestre = $row->id_trimestre;
            if (! empty($fechaIni)) {
                $m = (int) date('n', strtotime($fechaIni));
                $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
            }

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

            $cal = $validated['calificacion'] ?? 100;
            $cal = (int) $cal;
            if ($cal < 70) {
                $cal = 70;
            }
            if ($cal > 100) {
                $cal = 100;
            }

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
                'status'  => true,
                'message' => 'Registro actualizado correctamente.',
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            Log::error('Error al guardar PAC', [
                'message' => $th->getMessage(),
                'file'    => $th->getFile(),
                'line'    => $th->getLine(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('default.error_message'),
            ], 500);
        }
    }

    /**
     * ✅ Admin y revisor_est se comportan igual en este módulo
     */
    private function isAdminOrRevisorEst($user): bool
    {
        // Spatie
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin_oc', 'admin', 'revisor_est'])) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            if (
                $user->hasRole('admin_oc') ||
                $user->hasRole('admin') ||
                $user->hasRole('revisor_est')
            ) {
                return true;
            }
        }

        // método del modelo
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // rol_id clásico
        if (isset($user->rol_id) && (int) $user->rol_id === 1) {
            return true;
        }

        // booleano
        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        // nombres textuales posibles
        $roleCandidates = [
            $user->role ?? null,
            $user->rol ?? null,
            $user->rol_nombre ?? null,
            $user->nombre_rol ?? null,
            $user->perfil ?? null,
        ];

        foreach ($roleCandidates as $role) {
            $role = strtolower(trim((string) $role));
            if (in_array($role, ['admin_oc', 'admin', 'revisor_est'], true)) {
                return true;
            }
        }

        return false;
    }
}