<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Pac\TablePacModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TablePacController extends Controller
{
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController;
        $objectModel = new TablePacModel;

        try {
            $data = $templateTableController->validateAndSanitizePagination($request);

            $result = $objectModel->list(
                $data['limit'],
                $data['offset'],
                $data['search'],
                $data['select'],
                $request
            );

            return response()->json([
                'status' => true,
                'allRow' => $result['allRow'],
                'list'   => $result['list'],
                'row'    => $result['row'],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error en TablePacController@table', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'No se pudo cargar la tabla PAC.',
            ], 200);
        }
    }
}