<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\Collection\CollectionInstanceModel;
use App\Models\Pac\Collection\CollectionStatusModel;
use App\Models\Pac\Collection\CollectionTematicaModel;
use App\Models\Pac\DataPacModel;
use Illuminate\Http\Request;

class DataPacController extends Controller
{
    public function dataPac(Request $request)
    {
        try {
            // class
            $collectionStatusModel = new CollectionStatusModel;
            $collectionInstanceModel = new CollectionInstanceModel;
            $collectionTematicaModel = new CollectionTematicaModel;
            // Obtener los catalogos de cursos
            $dataPacModel = new DataPacModel;
            $data = $dataPacModel->dataPac($request->id);

            // agrega las hr a la variable si esta vacia
            if (empty($data->horas_real)) {
                $data->horas_real = $data->duracion_hrs;
            }

            // delcaracion de variables de selecion
            $listOptionStatus = $collectionStatusModel->listCollection();
            $listSelectStatus = isset($data->id_cat_estatus) ? $collectionStatusModel->listConllectionSelect($data->id_cat_estatus) : [];
            $listOptionInstance = $collectionInstanceModel->listCollection();
            $listSelectInstance = isset($data->id_instancia) ? $collectionInstanceModel->listConllectionSelect($data->id_instancia) : [];
            $listOptionTematica = $collectionTematicaModel->listCollection();
            $listSelectTematica = isset($data->id_cat_tematica) ? $collectionTematicaModel->listConllectionSelect($data->id_cat_tematica) : [];

            return response()->json([
                'status' => true, // Return successful response
                'data' => $data, // Send packaged data
                'listSelectStatus' => $listSelectStatus,
                'listOptionStatus' => $listOptionStatus,
                'listOptionInstance' => $listOptionInstance,
                'listSelectInstance' => $listSelectInstance,
                'listOptionTematica' => $listOptionTematica,
                'listSelectTematica' => $listSelectTematica,
            ], 200); // Respond with HTTP status 200
        } catch (\Throwable $th) {
            //\Log::info('error-....: '.$th);

            return response()->json([
                'status' => false, // return a JSON response with status false on error
                'message' => __('default.error_message'), // Default error message from config
            ], 200); // Respond with HTTP status 200 even on error
        }
    }
}
