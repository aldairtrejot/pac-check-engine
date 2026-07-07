<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Action\TableActionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TableActionController extends Controller
{
    /**
     * Endpoint AJAX para la tabla de Tipos de Acción.
     */
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController;
        $objectModel = new TableActionModel;

        try {
            // Validar y sanear paginación
            $data = $templateTableController->validateAndSanitizePagination($request);

            // Obtener datos
            $result = $objectModel->list(
                $data['limit'],
                $data['offset'],
                $data['search'],
                $data['select']
            );

            return response()->json([
                'status' => true,
                'allRow' => $result['allRow'],
                'list'   => $result['list'],
                'row'    => $result['row'],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error al cargar la tabla de acciones', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'No se pudo cargar la información de acciones.',
            ], 200);
        }
    }
}