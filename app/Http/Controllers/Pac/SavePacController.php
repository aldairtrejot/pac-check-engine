<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Log\LogDataModel;
use App\Models\Pac\EntityPacModel;
use App\Models\Pac\Helpers\GetTrimestreModel;
use App\Support\PacVisibility;
use Carbon\Carbon;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePacController extends Controller
{
    public function save(Request $request)
    {
        try {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);

            $request->merge([
                'm_observaciones' => strtoupper($purifier->purify(trim((string) $request->m_observaciones))),
            ]);

            return $this->storage($request);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }

    private function storage($request)
    {
        try {
            $getTrimestreModel = new GetTrimestreModel;
            $timestamp = Carbon::now();

            $rules = [
                'm_fecha_ini' => [
                    'required',
                    'after_or_equal:2025-01-01',
                    'before_or_equal:2025-12-31',
                ],
                'm_fecha_fin' => [
                    'required',
                    'after_or_equal:m_fecha_ini',
                    'before_or_equal:2025-12-31',
                ],
                'id_cat_estatus' => 'required',
                'id_instancia'   => 'required',
                'id_cat_tematica'=> 'required',
                'id_finalidad'   => 'nullable|integer',
                'm_observaciones'=> 'string|max:250',
                'm_horas_real'   => 'required|decimal:0,1|min:0.1|max:200.0',
                'id'             => 'required|integer',
            ];

            $request->validate($rules);

            // ✅ CERRAR BACKEND: valida permiso sobre ese id_empl_accion
            $this->authorizePacRecord((int) $request->id, $request->user());

            $updateData = [
                'fecha_ini'       => $request->m_fecha_ini,
                'fecha_fin'       => $request->m_fecha_fin,
                'id_cat_estatus'  => $request->id_cat_estatus,
                'id_instancia'    => $request->id_instancia,
                'id_cat_tematica' => $request->id_cat_tematica,
                'observaciones'   => $request->m_observaciones,
                'id_trimestre'    => $getTrimestreModel->getTrimestre($request->m_fecha_fin),
                'eval_aprendizaje'=> $request->m_eval_aprendizaje,
                'horas_real'      => $request->m_horas_real,
            ];

            if ($request->filled('id_finalidad')) {
                $updateData['id_finalidad'] = $request->id_finalidad;
            }

            EntityPacModel::where('id_empl_accion', (int) $request->id)->update($updateData);

            $logData = [
                'fecha_ini'       => $updateData['fecha_ini'],
                'fecha_fin'       => $updateData['fecha_fin'],
                'id_cat_estatus'  => $updateData['id_cat_estatus'],
                'id_instancia'    => $updateData['id_instancia'],
                'id_cat_tematica' => $updateData['id_cat_tematica'],
                'observaciones'   => $updateData['observaciones'],
                'eval_aprendizaje'=> $updateData['eval_aprendizaje'],
                'horas_real'      => $updateData['horas_real'],
                'creado_en'       => $timestamp,
                'id_usuario'      => Auth::user()->id,
                'id_empl_accion'  => (int) $request->id,
            ];

            LogDataModel::create($logData);

            return response()->json([
                'status'  => true,
                'message' => __('default.edit_success_message'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }

    /**
     * Verifica que el id_empl_accion pertenezca al ámbito del usuario
     * (entidad + nómina y si HRAES => clues).
     */
    private function authorizePacRecord(int $idEmplAccion, $user): void
    {
        $q = DB::table('public.a2_acciones_empleados')
            ->join('public.a2_acciones_capacitacion', function ($join) {
                $join->on(
                    DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                    '=',
                    'public.a2_acciones_capacitacion.id_puesto'
                );
            })
            ->where('public.a2_acciones_empleados.id_empl_accion', $idEmplAccion);

        PacVisibility::apply($q, $user, 'public.a2_acciones_capacitacion');

        if (! $q->exists()) {
            abort(403, 'No autorizado para modificar este registro.');
        }
    }
}