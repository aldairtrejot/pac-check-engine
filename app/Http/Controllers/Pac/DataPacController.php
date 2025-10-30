<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\DataPacModel;
use Illuminate\Http\Request;

class DataPacController extends Controller
{
    public function dataPac(Request $request)
    {
        try {
            // Obtener los catalogos de cursos
            $dataPacModel = new DataPacModel;
            $data = $dataPacModel->dataPac($request->id);

            \Log::info($data->id_cat_estatus);

            return response()->json([
                'status' => true, // Return successful response
                'data' => $data, // Send packaged data

            ], 200); // Respond with HTTP status 200
        } catch (\Throwable $th) {
            \Log::info('erros: '.$th);

            return response()->json([
                'status' => false, // return a JSON response with status false on error
                'message' => __('default.error_message'), // Default error message from config
            ], 200); // Respond with HTTP status 200 even on error
        }
    }
}
