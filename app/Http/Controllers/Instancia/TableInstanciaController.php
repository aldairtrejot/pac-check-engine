<?php

namespace App\Http\Controllers\Instancia;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Instancia\TableInstanciaModel;
use Illuminate\Http\Request;

class TableInstanciaController extends Controller
{
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController;
        $objectModel = new TableInstanciaModel;

        try {
            $data = $templateTableController->validateAndSanitizePagination($request);

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
