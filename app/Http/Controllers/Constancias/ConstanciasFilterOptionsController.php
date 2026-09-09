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
                'validaciones'  => [],
            ], 401);
        }

        $scope = ConstanciaVisibilityByName::resolveScope((int) $user->id);
        $isAdmin = (bool) ($scope['is_admin_global'] ?? false);

        $request->validate([
            'entidad' => 'nullable|string|max:255',
        ]);

        $entidad = trim((string) $request->input('entidad', ''));

        return response()->json([
            'status'       => true,
            'is_admin'     => $isAdmin,
            'entidades'    => $isAdmin ? $this->entidadOptions() : [],
            'tipos_nomina' => $isAdmin ? $this->tipoNominaOptions() : [],
            'clues'        => $isAdmin ? $this->cluesOptions($entidad) : [],
            'validaciones' => $this->validacionOptions($user),
        ]);
    }

    private function baseVisibleConstanciasQuery($user): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('public.tbl_constancias as c')
            ->whereNotNull('c.id_puesto')
            ->whereRaw("BTRIM(COALESCE(c.id_puesto::text, '')) <> ''")
            ->whereNotNull('c.estatus');

        ConstanciaVisibilityByName::apply($query, $user, 'c');

        return $query;
    }

    private function validacionOptions($user): array
    {
        return $this->baseVisibleConstanciasQuery($user)
            ->whereNotNull('c.val_plantilla')
            ->whereRaw("BTRIM(COALESCE(c.val_plantilla::text, '')) <> ''")
            ->selectRaw("UPPER(BTRIM(c.val_plantilla::text)) AS value")
            ->selectRaw("UPPER(BTRIM(c.val_plantilla::text)) AS label")
            ->groupByRaw("UPPER(BTRIM(c.val_plantilla::text))")
            ->orderBy('value')
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->value,
                'label' => (string) $row->label,
            ])
            ->all();
    }

    private function entidadOptions(): array
    {
        return DB::table('public.tbl_constancias as c')
            ->whereNotNull('c.entidad')
            ->whereRaw("BTRIM(COALESCE(c.entidad::text, '')) <> ''")
            ->whereNotNull('c.id_puesto')
            ->whereRaw("BTRIM(COALESCE(c.id_puesto::text, '')) <> ''")
            ->whereNotNull('c.estatus')
            ->selectRaw("UPPER(BTRIM(c.entidad::text)) AS value")
            ->selectRaw("UPPER(BTRIM(c.entidad::text)) AS label")
            ->groupByRaw("UPPER(BTRIM(c.entidad::text))")
            ->orderBy('value')
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->value,
                'label' => (string) $row->label,
            ])
            ->all();
    }

    private function tipoNominaOptions(): array
    {
        return DB::table('public.tbl_constancias as c')
            ->leftJoin(
                'administracion.cat_tipo_nomina as ctn',
                DB::raw("UPPER(BTRIM(c.tipo_nomina::text))"),
                '=',
                DB::raw("UPPER(BTRIM(ctn.codigo::text))")
            )
            ->whereNotNull('c.tipo_nomina')
            ->whereRaw("BTRIM(COALESCE(c.tipo_nomina::text, '')) <> ''")
            ->whereNotNull('c.id_puesto')
            ->whereRaw("BTRIM(COALESCE(c.id_puesto::text, '')) <> ''")
            ->whereNotNull('c.estatus')
            ->selectRaw("UPPER(BTRIM(c.tipo_nomina::text)) AS value")
            ->selectRaw("
                COALESCE(
                    UPPER(BTRIM(c.tipo_nomina::text)) || ' - ' || MAX(NULLIF(BTRIM(ctn.nombre::text), '')),
                    UPPER(BTRIM(c.tipo_nomina::text))
                ) AS label
            ")
            ->groupByRaw("UPPER(BTRIM(c.tipo_nomina::text))")
            ->orderBy('value')
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->value,
                'label' => (string) $row->label,
            ])
            ->all();
    }

    private function cluesOptions(string $entidad = ''): array
    {
        $query = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                'administracion.cat_clues as cc',
                DB::raw("UPPER(BTRIM(c.clues::text))"),
                '=',
                DB::raw("UPPER(BTRIM(cc.clues::text))")
            )
            ->whereNotNull('c.clues')
            ->whereRaw("BTRIM(COALESCE(c.clues::text, '')) <> ''")
            ->whereNotNull('c.id_puesto')
            ->whereRaw("BTRIM(COALESCE(c.id_puesto::text, '')) <> ''")
            ->whereNotNull('c.estatus');

        if ($entidad !== '') {
            $query->whereRaw(
                "UPPER(BTRIM(COALESCE(c.entidad::text, ''))) = ?",
                [$this->norm($entidad)]
            );
        }

        return $query
            ->selectRaw("UPPER(BTRIM(c.clues::text)) AS value")
            ->selectRaw("
                COALESCE(
                    UPPER(BTRIM(c.clues::text)) || ' - ' || MAX(NULLIF(BTRIM(cc.descripcion::text), '')),
                    UPPER(BTRIM(c.clues::text))
                ) AS label
            ")
            ->groupByRaw("UPPER(BTRIM(c.clues::text))")
            ->orderBy('value')
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->value,
                'label' => (string) $row->label,
            ])
            ->all();
    }

    private function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
