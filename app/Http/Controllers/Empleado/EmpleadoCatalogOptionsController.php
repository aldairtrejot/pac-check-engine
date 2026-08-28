<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Support\EmpleadoCatalogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmpleadoCatalogOptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin_oc,supervisor_oc');
    }

    public function clues(Request $request)
    {
        try {
            $catalogKey = trim((string) $request->query('key', ''));

            if ($catalogKey !== '') {
                $clue = EmpleadoCatalogs::findCluesByCatalogKey($catalogKey);

                return response()->json([
                    'status' => true,
                    'options' => $clue ? [$clue] : [],
                ]);
            }

            return response()->json([
                'status' => true,
                'options' => EmpleadoCatalogs::searchClues($request->query('q', ''), 50),
            ]);

        } catch (\Throwable $th) {
            Log::error('Error al consultar catálogo de CLUES para empleado', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'No se pudo consultar el catálogo de CLUES.',
                'options' => [],
            ], 500);
        }
    }
}
