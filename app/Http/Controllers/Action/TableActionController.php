<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Action\TableActionModel;
use Illuminate\Http\Request;

class TableActionController extends Controller
{
    /**
     * Endpoint AJAX para la tabla de Tipos de Acción
     */
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController;   // Helper de paginación
        $objectModel = new TableActionModel;                      // Modelo de tabla

        try {
            // Validar y sanear paginación
            $data = $templateTableController->validateAndSanitizePagination($request);

            // Obtener datos
            $result = $objectModel->list(
                $data['limit'],
                $data['offset'],
                $data['search'],
                $data['select'],
            );

            return response()->json([
                'status' => true,
                'allRow' => $result['allRow'],
                'list'   => $result['list'],
                'row'    => $result['row'],
            ], 200);
        } catch (\Exception $e) {
            \Log::info($e);

            return response()->json([
                'status'  => false,
                'message' => 'Error',
            ], 200);
        }
    }
}
