<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateEstatusConstanciasController extends Controller
{
    private const ESTATUS_ACEPTADA  = 2;
    private const ESTATUS_RECHAZADA = 3;

    public function update(Request $request)
    {
        $id = trim((string) $request->input('id_respuesta', ''));
        $accion = strtoupper(trim((string) $request->input('accion', ''))); // ACEPTAR | RECHAZAR

        if ($id === '') {
            return response()->json(['status' => false, 'message' => 'id_respuesta inválido.'], 422);
        }

        if (!in_array($accion, ['ACEPTAR', 'RECHAZAR'], true)) {
            return response()->json(['status' => false, 'message' => 'Acción inválida.'], 422);
        }

        $estatus = $accion === 'ACEPTAR' ? self::ESTATUS_ACEPTADA : self::ESTATUS_RECHAZADA;

        // ✅ IMPORTANTE: forzamos schema public
        $updated = DB::table('public.tbl_constancias')
            ->where('id_respuesta', $id)
            ->update([
                'estatus' => $estatus,
                'fecha_ini_accion' => DB::raw("COALESCE(fecha_ini_accion, CURRENT_DATE)"),
                'fecha_ultima_accion' => DB::raw("CURRENT_DATE"),
            ]);

        if (!$updated) {
            return response()->json([
                'status' => false,
                'message' => 'No se pudo actualizar (¿registro no existe?).',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Estatus actualizado correctamente.',
            'estatus' => $estatus,
        ]);
    }
}