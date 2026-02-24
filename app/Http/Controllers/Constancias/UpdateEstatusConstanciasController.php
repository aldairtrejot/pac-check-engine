<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateEstatusConstanciasController extends Controller
{
    public function update(Request $request)
    {
        $id = (int) $request->input('id', 0);
        $estatus = trim((string) $request->input('estatus', ''));

        $allowed = ['ACEPTADA', 'RECHAZADA'];

        if ($id <= 0) {
            return response()->json(['status' => false, 'message' => 'ID inválido.'], 422);
        }
        if (!in_array($estatus, $allowed, true)) {
            return response()->json(['status' => false, 'message' => 'Estatus inválido.'], 422);
        }

        // ✅ TODO: Cambiar a la tabla/columnas reales cuando BD quede definida
        $updated = DB::table('pac_constancias')
            ->where('id', $id)
            ->update([
                'estatus' => $estatus,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'status' => false,
                'message' => 'No se pudo actualizar (¿registro no existe?).',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => "Estatus actualizado a {$estatus}.",
            'estatus' => $estatus,
        ]);
    }
}