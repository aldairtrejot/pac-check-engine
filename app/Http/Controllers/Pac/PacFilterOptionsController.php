<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Support\PacVisibility;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PacFilterOptionsController extends Controller
{
    public function options(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json([
                'status'       => false,
                'message'      => 'No autenticado.',
                'is_admin'     => false,
                'entidades'    => [],
                'tipos_nomina' => [],
                'clues'        => [],
            ], 401);
        }

        if (! PacVisibility::isAdminGlobal($user)) {
            return response()->json([
                'status'       => false,
                'message'      => 'No tienes permisos para consultar filtros administrativos.',
                'is_admin'     => false,
                'entidades'    => [],
                'tipos_nomina' => [],
                'clues'        => [],
            ], 403);
        }

        $request->validate([
            'entidad' => 'nullable|string|max:255',
        ]);

        $entidad = trim((string) $request->input('entidad', ''));

        return response()->json([
            'status'       => true,
            'is_admin'     => true,
            'entidades'    => $this->entidadOptions(),
            'tipos_nomina' => $this->tipoNominaOptions(),
            'clues'        => $this->cluesOptions($entidad),
        ], 200);
    }

    private function entidadOptions(): array
    {
        return $this->basePlantillaQuery()
            ->whereNotNull('c.entidad')
            ->whereRaw("BTRIM(COALESCE(c.entidad::text, '')) <> ''")
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
        return $this->basePlantillaQuery()
            ->leftJoin(
                'administracion.cat_tipo_nomina as ctn',
                DB::raw("UPPER(BTRIM(c.nomina::text))"),
                '=',
                DB::raw("UPPER(BTRIM(ctn.codigo::text))")
            )
            ->whereNotNull('c.nomina')
            ->whereRaw("BTRIM(COALESCE(c.nomina::text, '')) <> ''")
            ->selectRaw("UPPER(BTRIM(c.nomina::text)) AS value")
            ->selectRaw("
                COALESCE(
                    UPPER(BTRIM(c.nomina::text)) || ' - ' || MAX(NULLIF(BTRIM(ctn.nombre::text), '')),
                    UPPER(BTRIM(c.nomina::text))
                ) AS label
            ")
            ->groupByRaw("UPPER(BTRIM(c.nomina::text))")
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
        if ($entidad === '') {
            return [];
        }

        $query = $this->basePlantillaQuery()
            ->leftJoin(
                'administracion.cat_clues as cc',
                DB::raw("UPPER(BTRIM(c.clave_clues::text))"),
                '=',
                DB::raw("UPPER(BTRIM(cc.clues::text))")
            )
            ->whereNotNull('c.clave_clues')
            ->whereRaw("BTRIM(COALESCE(c.clave_clues::text, '')) <> ''");

        $query->whereRaw(
            "UPPER(BTRIM(COALESCE(c.entidad::text, ''))) = ?",
            [$this->norm($entidad)]
        );

        return $query
            ->selectRaw("UPPER(BTRIM(c.clave_clues::text)) AS value")
            ->selectRaw("
                COALESCE(
                    UPPER(BTRIM(c.clave_clues::text)) || ' - ' ||
                    COALESCE(
                        MAX(NULLIF(BTRIM(c.descripcion_clues::text), '')),
                        MAX(NULLIF(BTRIM(cc.descripcion::text), ''))
                    ),
                    UPPER(BTRIM(c.clave_clues::text))
                ) AS label
            ")
            ->groupByRaw("UPPER(BTRIM(c.clave_clues::text))")
            ->orderBy('value')
            ->get()
            ->map(fn ($row) => [
                'value' => (string) $row->value,
                'label' => (string) $row->label,
            ])
            ->all();
    }

    private function basePlantillaQuery(): Builder
    {
        $query = DB::table('public.a2_acciones_empleados as e')
            ->join('public.a1_cat_acciones as a', 'e.id_accion', '=', 'a.id_accion')
            ->join('public.a2_acciones_capacitacion as c', function ($join) {
                $join->on(
                    DB::raw("
                        CASE
                            WHEN TRIM(e.id_puesto) ~ '^[0-9]+$'
                            THEN TRIM(e.id_puesto)::INTEGER
                            ELSE NULL
                        END
                    "),
                    '=',
                    'c.id_puesto'
                )
                ->whereRaw(
                    'UPPER(TRIM(public.unaccent(e.curp))) = UPPER(TRIM(public.unaccent(c.curp)))'
                );
            });

        $this->applyVisibleCourseFilters($query);
        $this->applyActiveEmployeeFilter($query);

        return $query;
    }

    private function applyVisibleCourseFilters(Builder $query): void
    {
        $query->where(function ($q) {
            $q->where(function ($vigente) {
                $vigente->whereRaw("TRIM(UPPER(COALESCE(a.estatus, ''))) = 'VIGENTE'")
                    ->where(function ($estadoEmpleado) {
                        $estadoEmpleado->whereNull('e.id_cat_estatus')
                            ->orWhereIn('e.id_cat_estatus', [1, 2]);
                    });
            })
            ->orWhere(function ($historico) {
                $historico->whereNotNull('e.id_cat_estatus')
                    ->whereNotNull('e.fecha_ini')
                    ->whereNotNull('e.fecha_fin')
                    ->whereNotNull('e.id_trimestre')
                    ->whereNotNull('e.id_instancia')
                    ->whereRaw("TRIM(e.id_instancia) <> ''")
                    ->whereNotNull('e.id_cat_tematica')
                    ->whereRaw("TRIM(e.id_cat_tematica) <> ''");
            });
        });
    }

    private function applyActiveEmployeeFilter(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('c.activo')
                ->orWhereIn('c.activo', [1, 2]);
        });
    }

    private function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}
