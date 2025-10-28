<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\CollectionPacModel;

class MainPacController extends Controller
{
    public function mainPac()
    {
        try {
            // Obtener los catalogos de cursos
            $collectionPacModel = new CollectionPacModel;
            $listOptionsAcction = $collectionPacModel->listCollection();
            $listSelectAcction = [];

            return response()->json([
                'status' => true, // Return successful response
                'listOptionsAcction' => $listOptionsAcction, // Send packaged data
                'listSelectAcction' => $listSelectAcction, // Send packaged data
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
