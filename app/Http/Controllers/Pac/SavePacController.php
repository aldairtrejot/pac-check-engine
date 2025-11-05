<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Log\LogDataModel;
use App\Models\Pac\EntityPacModel;
use App\Models\Pac\Helpers\GetTrimestreModel;
use Carbon\Carbon;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SavePacController extends Controller
{
    /**
     * The function sanitizes the data and returns the validation function, as well as the editing or aggregation.
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        try {
            $config = HTMLPurifier_Config::createDefault(); // create a default HTMLPurifier configuration
            $purifier = new HTMLPurifier($config); // instantiate HTMLPurifier with the config

            $request->merge([
                'm_observaciones' => strtoupper($purifier->purify(trim($request->m_observaciones))), // sanitize
            ]);

            return $this->storage($request); // return to storage

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false, // return a JSON response with status false on error
                'message' => __('default.error_message'), // Message error
            ], 200); // respond with HTTP status code 200 even in error case
        }
    }

    /**
     * The function validates the information, and saves either new records or updates, where it records its log.
     *
     * @param  mixed  $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function storage($request)
    {
        try {
            $getTrimestreModel = new GetTrimestreModel;
            $timestamp = Carbon::now(); // Get current timestamp

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
                'id_finalidad'   => 'nullable|integer', // 🔹 NUEVO
                'm_observaciones'=> 'string|max:250',
                'm_horas_real'   => 'required|decimal:0,1|min:0.1|max:200.0'
            ];

            $request->validate($rules); // Run validation

            // Datos para actualizar en a2_acciones_empleados
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

            // 🔹 SOLO actualizamos id_finalidad si viene en el request
            if ($request->filled('id_finalidad')) {
                $updateData['id_finalidad'] = $request->id_finalidad;
            }

            // Actualiza datos del empleado-curso
            EntityPacModel::where('id_empl_accion', $request->id)->update($updateData);

            // Datos para la tabla de log (sin id_trimestre y sin id_finalidad para no romper nada)
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
                'id_empl_accion'  => $request->id,
            ];

            LogDataModel::create($logData);
            $message = __('default.edit_success_message');

            return response()->json([
                'status'  => true, // Success response
                'message' => $message,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(), // Return validation errors
            ], 422); // HTTP 422 for validation errors
        } catch (\Throwable $th) {
            // \Log::info($th);

            return response()->json([
                'status' => false,
                'message' => __('default.error_message'), // Default error message
            ], 200); // Return general error response
        }
    }
}
