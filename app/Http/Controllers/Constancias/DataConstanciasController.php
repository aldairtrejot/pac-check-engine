<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataConstanciasController extends Controller
{
    public function data(Request $request)
    {
        $id = (int) $request->input('id', 0);

        if ($id <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'ID inválido.',
            ], 422);
        }

        // ✅ TODO: Cambiar a la tabla/columnas reales cuando BD quede definida
        $row = DB::table('pac_constancias')
            ->where('id', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'status' => false,
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        // Aquí vendrán "datos aún no definidos" (puede ser jsonb, columnas extra, joins, etc.)
        // Por ahora regresamos el registro tal cual.
        return response()->json([
            'status' => true,
            'data' => $row,
        ]);
    }
}