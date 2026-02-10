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
            $collectionInstanceModel  = new CollectionInstanceModel;
            $collectionTematicaModel  = new CollectionTematicaModel;
            $collectionFinalidadModel = new CollectionFinalidadModel;

            $dataPacModel = new DataPacModel;

            // ✅ ahora pasa user para aplicar visibilidad
            $data = $dataPacModel->dataPac($request->id, $request->user());

            if (! $data) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Registro no encontrado o sin permiso.',
                ], 200);
            }

            if (empty($data->horas_real)) {
                $data->horas_real = $data->duracion_hrs;
            }

            $totalHorasReal = DB::table('public.a2_acciones_empleados')
                ->whereRaw('UPPER(TRIM(curp)) = UPPER(TRIM(?))', [$data->curp])
                ->sum('horas_real');

            $listOptionStatus = DB::table('public.cat_estatus')
                ->select('id_cat_estatus as id', 'descripcion')
                ->whereIn('id_cat_estatus', [1, 2, 3])
                ->orderBy('id_cat_estatus')
                ->get();

            if (isset($data->id_cat_estatus) && in_array((int) $data->id_cat_estatus, [1, 2, 3], true)) {
                $listSelectStatus = $listOptionStatus
                    ->where('id', (int) $data->id_cat_estatus)
                    ->values()
                    ->all();
            } else {
                $listSelectStatus = [];
            }

            $listOptionInstance = $collectionInstanceModel->listCollection();
            $listSelectInstance = isset($data->id_instancia)
                ? $collectionInstanceModel->listConllectionSelect($data->id_instancia)
                : [];

            $listOptionTematica = $collectionTematicaModel->listCollection();

            if (! empty($data->id_cat_tematica)) {
                $listSelectTematica = $collectionTematicaModel->listConllectionSelect($data->id_cat_tematica);
            } else {
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

            $listOptionFinalidad = $collectionFinalidadModel->listCollection();

            if (! empty($data->id_finalidad)) {
                $listSelectFinalidad = $collectionFinalidadModel->listConllectionSelect($data->id_finalidad);
            } else {
                $listSelectFinalidad = $collectionFinalidadModel->listConllectionSelect(6);
            }

            return response()->json([
                'status'              => true,
                'data'                => $data,
                'totalHorasReal'      => (float) $totalHorasReal,
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