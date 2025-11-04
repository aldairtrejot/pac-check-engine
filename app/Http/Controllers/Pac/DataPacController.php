<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\Collection\CollectionInstanceModel;
use App\Models\Pac\Collection\CollectionStatusModel;
use App\Models\Pac\Collection\CollectionTematicaModel;
use App\Models\Pac\DataPacModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataPacController extends Controller
{
    public function dataPac(Request $request)
    {
        try {
            // Catálogos
            $collectionStatusModel   = new CollectionStatusModel;
            $collectionInstanceModel = new CollectionInstanceModel;
            $collectionTematicaModel = new CollectionTematicaModel;

            // Datos del empleado / acción
            $dataPacModel = new DataPacModel;
            $data = $dataPacModel->dataPac($request->id);

            if (! $data) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Registro no encontrado.',
                ], 200);
            }

            // Si no tiene horas_real, se llena con la duración del curso
            if (empty($data->horas_real)) {
                $data->horas_real = $data->duracion_hrs;
            }

            // ===== Estatus =====
            $listOptionStatus = $collectionStatusModel->listCollection();
            $listSelectStatus = isset($data->id_cat_estatus)
                ? $collectionStatusModel->listConllectionSelect($data->id_cat_estatus)
                : [];

            // ===== Instancia =====
            $listOptionInstance = $collectionInstanceModel->listCollection();
            $listSelectInstance = isset($data->id_instancia)
                ? $collectionInstanceModel->listConllectionSelect($data->id_instancia)
                : [];

            // ===== Temática =====
            $listOptionTematica = $collectionTematicaModel->listCollection();

            if (isset($data->id_cat_tematica) && $data->id_cat_tematica !== null && $data->id_cat_tematica !== '') {
                // Ya hay temática guardada en a2_acciones_empleados → se respeta
                $listSelectTematica = $collectionTematicaModel->listConllectionSelect($data->id_cat_tematica);
            } else {
                // No hay temática guardada → intentamos tomar la temática del curso (a1_cat_acciones.tematica)
                $listSelectTematica = [];

                if (! empty($data->tematica_accion)) {
                    $row = DB::table('public.cat_tematica')
                        ->select(
                            'public.cat_tematica.id_tematica as id',
                            'public.cat_tematica.tematica as descripcion'
                        )
                        ->whereRaw(
                            'TRIM(UPPER(public.cat_tematica.tematica)) = TRIM(UPPER(?))',
                            [$data->tematica_accion]
                        )
                        ->first();

                    if ($row) {
                        // Esto es lo que verá seleccionado por defecto en el combo de Temática
                        $listSelectTematica = [$row];
                    }
                }
            }

            return response()->json([
                'status'             => true,
                'data'               => $data,
                'listSelectStatus'   => $listSelectStatus,
                'listOptionStatus'   => $listOptionStatus,
                'listOptionInstance' => $listOptionInstance,
                'listSelectInstance' => $listSelectInstance,
                'listOptionTematica' => $listOptionTematica,
                'listSelectTematica' => $listSelectTematica,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }
}
