<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helpers\TemplateTableController;
use App\Models\Pac\TablePacModel;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TablePacController extends Controller
{
    public function table(Request $request)
    {
        $templateTableController = new TemplateTableController;
        $objectModel = new TablePacModel;
        $user = auth()->user();
        $isAdmin = PacVisibility::isAdminGlobal($user);

        if (! $isAdmin && $this->hasAdminFilters($request)) {
            return response()->json([
                'status'   => false,
                'message'  => 'No tienes permisos para usar filtros administrativos.',
                'allRow'   => 0,
                'list'     => [],
                'row'      => 0,
                'is_admin' => false,
            ], 403);
        }

        if ($isAdmin && $this->hasCluesWithoutEntidad($request)) {
            return response()->json([
                'status'   => false,
                'message'  => 'Selecciona una entidad antes de filtrar por CLUES.',
                'allRow'   => 0,
                'list'     => [],
                'row'      => 0,
                'is_admin' => true,
            ], 422);
        }

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
                'is_admin' => $isAdmin,
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

    private function hasAdminFilters(Request $request): bool
    {
        foreach (['entidad', 'tipo_nomina', 'clues'] as $field) {
            if (trim((string) $request->input($field, '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasCluesWithoutEntidad(Request $request): bool
    {
        return trim((string) $request->input('clues', '')) !== ''
            && trim((string) $request->input('entidad', '')) === '';
    }
}
