<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\Collection\CollectionInstanceModel;
use App\Models\Pac\Collection\CollectionTematicaModel;
use App\Models\Pac\Collection\CollectionFinalidadModel;
use App\Models\Pac\DataPacModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataPacController extends Controller
{
    public function dataPac(Request $request)
    {
        try {
            // Catálogos
            $collectionInstanceModel  = new CollectionInstanceModel;
            $collectionTematicaModel  = new CollectionTematicaModel;
            $collectionFinalidadModel = new CollectionFinalidadModel;

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

            /*
             * ===== ESTATUS (solo ALTA / BAJA) =====
             * cat_estatus:
             * 2 = ALTA
             * 3 = BAJA
             */
            $listOptionStatus = DB::table('public.cat_estatus')
                ->select('id_cat_estatus as id', 'descripcion')
                ->whereIn('id_cat_estatus', [2, 3])
                ->orderBy('id_cat_estatus')
                ->get();

            if (isset($data->id_cat_estatus) && in_array((int) $data->id_cat_estatus, [2, 3], true)) {
                // Solo marcamos seleccionado si es ALTA o BAJA
                $listSelectStatus = $listOptionStatus
                    ->where('id', (int) $data->id_cat_estatus)
                    ->values()
                    ->all();
            } else {
                $listSelectStatus = [];
            }

            // ===== Instancia =====
            $listOptionInstance = $collectionInstanceModel->listCollection();
            $listSelectInstance = isset($data->id_instancia)
                ? $collectionInstanceModel->listConllectionSelect($data->id_instancia)
                : [];

            // ===== Temática =====
            $listOptionTematica = $collectionTematicaModel->listCollection();

            if (!empty($data->id_cat_tematica)) {
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
                        $listSelectTematica = [$row];
                    }
                }
            }

            // ===== Finalidad =====
            $listOptionFinalidad = $collectionFinalidadModel->listCollection();

            if (! empty($data->id_finalidad)) {
                // Si ya trae finalidad en a2_acciones_empleados, se respeta
                $listSelectFinalidad = $collectionFinalidadModel->listConllectionSelect($data->id_finalidad);
            } else {
                // Si viene null (registros viejos), sugerimos 6 como default si existe
                $listSelectFinalidad = $collectionFinalidadModel->listConllectionSelect(6);
            }

            return response()->json([
                'status'              => true,
                'data'                => $data,
                'listOptionStatus'    => $listOptionStatus,
                'listSelectStatus'    => $listSelectStatus,
                'listOptionInstance'  => $listOptionInstance,
                'listSelectInstance'  => $listSelectInstance,
                'listOptionTematica'  => $listOptionTematica,
                'listSelectTematica'  => $listSelectTematica,
                'listOptionFinalidad' => $listOptionFinalidad,
                'listSelectFinalidad' => $listSelectFinalidad,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => __('default.error_message'),
            ], 200);
        }
    }
}

