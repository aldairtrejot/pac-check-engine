<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Support\ConstanciaVisibilityByName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConstanciasFilterOptionsController extends Controller
{
    public function options(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'status'        => false,
                'message'       => 'No autenticado.',
                'is_admin'      => false,
                'entidades'     => [],
                'tipos_nomina'  => [],
                'clues'         => [],
            ], 401);
        }

        $scope = ConstanciaVisibilityByName::resolveScope((int) $user->id);
        $isAdmin = (bool) ($scope['is_admin_global'] ?? false);

        /*
        |--------------------------------------------------------------------------
        | Solo ADMIN puede recibir opciones para estos filtros.
        |--------------------------------------------------------------------------
        | Supervisores y revisores no deben visualizar estos combos.
        */
        if (! $isAdmin) {
            return response()->json([
                'status'        => true,
                'is_admin'      => false,
                'entidades'     => [],
                'tipos_nomina'  => [],
                'clues'         => [],
            ]);
        }

        return response()->json([
            'status'       => true,
            'is_admin'     => true,
            'entidades'    => $this->distinctOptions('entidad'),
            'tipos_nomina' => $this->distinctOptions('tipo_nomina'),
            'clues'        => $this->distinctOptions('clues'),
        ]);
    }

    private function distinctOptions(string $column): array
    {
        $allowed = [
            'entidad',
            'tipo_nomina',
            'clues',
        ];

        if (! in_array($column, $allowed, true)) {
            return [];
        }

        return DB::table('public.tbl_constancias as c')
            ->whereNotNull("c.{$column}")
            ->whereRaw("BTRIM(COALESCE(c.{$column}::text, '')) <> ''")
            ->whereNotNull('c.id_puesto')
            ->whereRaw("BTRIM(COALESCE(c.id_puesto::text, '')) <> ''")
            ->whereNotNull('c.estatus')
            ->selectRaw("DISTINCT UPPER(BTRIM(c.{$column}::text)) AS value")
            ->orderBy('value')
            ->pluck('value')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->values()
            ->all();
    }
}