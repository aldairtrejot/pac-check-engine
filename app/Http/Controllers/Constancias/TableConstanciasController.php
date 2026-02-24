<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableConstanciasController extends Controller
{
    public function table(Request $request)
    {
        $limit  = (int) ($request->input('limit', 5));
        $offset = (int) ($request->input('offset', 0));

        $curp  = trim((string) $request->input('curp', ''));
        $curso = trim((string) $request->input('curso', ''));
        $anio  = trim((string) $request->input('anio', ''));
        $search = trim((string) $request->input('search', ''));

        // ✅ TODO: Cambiar a la tabla/columnas reales cuando BD quede definida
        $q = DB::table('pac_constancias')
            ->select([
                'id',
                'curp',
                'nombre_curso',
                'anio',
                'estatus',
            ]);

        // Filtros específicos
        if ($curp !== '') {
            $q->where('curp', 'ILIKE', "%{$curp}%");
        }
        if ($curso !== '') {
            $q->where('nombre_curso', 'ILIKE', "%{$curso}%");
        }
        if ($anio !== '') {
            $q->where('anio', (int) $anio);
        }

        // Buscador general (opcional)
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('curp', 'ILIKE', "%{$search}%")
                  ->orWhere('nombre_curso', 'ILIKE', "%{$search}%")
                  ->orWhereRaw("CAST(anio AS TEXT) ILIKE ?", ["%{$search}%"])
                  ->orWhere('estatus', 'ILIKE', "%{$search}%");
            });
        }

        $allRow = (clone $q)->count();

        $list = $q->orderByDesc('id')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'list' => $list,
            'allRow' => $allRow,
            'row' => count($list),
        ]);
    }
}