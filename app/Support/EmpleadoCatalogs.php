<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmpleadoCatalogs
{
    public static function puestos(): Collection
    {
        return DB::table('public.cat_puesto_bi')
            ->selectRaw("
                UPPER(BTRIM(codigo_puesto)) as codigo_puesto,
                UPPER(BTRIM(COALESCE(puesto, ''))) as puesto,
                UPPER(BTRIM(COALESCE(nivel, ''))) as nivel,
                UPPER(BTRIM(COALESCE(estatus, ''))) as estatus
            ")
            ->whereNotNull('codigo_puesto')
            ->whereRaw("BTRIM(codigo_puesto) <> ''")
            ->orderByRaw("UPPER(BTRIM(COALESCE(puesto, '')))")
            ->orderByRaw("UPPER(BTRIM(codigo_puesto))")
            ->get()
            ->map(fn ($row) => self::formatPuesto($row));
    }

    public static function findPuestoByCodigo(?string $codigo): ?object
    {
        $codigo = self::norm($codigo);

        if ($codigo === '') {
            return null;
        }

        $row = DB::table('public.cat_puesto_bi')
            ->selectRaw("
                UPPER(BTRIM(codigo_puesto)) as codigo_puesto,
                UPPER(BTRIM(COALESCE(puesto, ''))) as puesto,
                UPPER(BTRIM(COALESCE(nivel, ''))) as nivel,
                UPPER(BTRIM(COALESCE(estatus, ''))) as estatus
            ")
            ->whereRaw("UPPER(BTRIM(codigo_puesto)) = ?", [$codigo])
            ->first();

        return $row ? self::formatPuesto($row) : null;
    }

    public static function clues(): Collection
    {
        return self::cluesBaseQuery()
            ->orderByRaw("UPPER(BTRIM(COALESCE(t.entidad, '')))")
            ->orderByRaw("UPPER(BTRIM(COALESCE(NULLIF(BTRIM(t.descripcion_clues), ''), t.clave_clues)))")
            ->orderByRaw("UPPER(BTRIM(t.clave_clues))")
            ->get()
            ->map(fn ($row) => self::formatClues($row));
    }

    public static function searchClues(?string $search, int $limit = 50): Collection
    {
        $search = self::norm($search);
        $limit = max(1, min($limit, 100));

        if (mb_strlen($search, 'UTF-8') < 2) {
            return collect();
        }

        $terms = collect(preg_split('/\s+/', $search) ?: [])
            ->map(fn ($term) => trim((string) $term))
            ->filter(fn ($term) => $term !== '')
            ->take(5)
            ->values();

        $query = self::cluesBaseQuery();

        foreach ($terms as $term) {
            $term = '%' . $term . '%';

            $query->where(function ($query) use ($term) {
                $query->whereRaw(
                    "UPPER(public.unaccent(t.clave_clues)) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(t.descripcion_clues, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(t.entidad, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(t.nomina, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                );
            });
        }

        return $query
            ->orderByRaw("UPPER(BTRIM(COALESCE(t.entidad, '')))")
            ->orderByRaw("UPPER(BTRIM(COALESCE(NULLIF(BTRIM(t.descripcion_clues), ''), t.clave_clues)))")
            ->orderByRaw("UPPER(BTRIM(t.clave_clues))")
            ->limit($limit)
            ->get()
            ->map(fn ($row) => self::formatClues($row));
    }

    public static function findCluesByCatalogKey(?string $catalogKey): ?object
    {
        $decoded = self::decodeCluesCatalogKey($catalogKey);

        if (! $decoded) {
            return null;
        }

        $row = self::cluesBaseQuery()
            ->whereRaw("UPPER(BTRIM(t.clave_clues)) = ?", [$decoded['clave_clues']])
            ->whereRaw(
                "UPPER(BTRIM(COALESCE(NULLIF(BTRIM(t.descripcion_clues), ''), t.clave_clues))) = ?",
                [$decoded['descripcion_clues']]
            )
            ->whereRaw("UPPER(BTRIM(COALESCE(t.nomina, ''))) = ?", [$decoded['nomina']])
            ->whereRaw("UPPER(BTRIM(COALESCE(t.entidad, ''))) = ?", [$decoded['entidad']])
            ->first();

        return $row ? self::formatClues($row) : null;
    }

    public static function findCluesByClave(?string $claveClues): ?object
    {
        $claveClues = self::norm($claveClues);

        if ($claveClues === '') {
            return null;
        }

        $row = self::cluesBaseQuery()
            ->whereRaw("UPPER(BTRIM(t.clave_clues)) = ?", [$claveClues])
            ->orderByRaw("CASE WHEN BTRIM(COALESCE(t.descripcion_clues, '')) = '' THEN 1 ELSE 0 END")
            ->orderByRaw("UPPER(BTRIM(COALESCE(t.entidad, '')))")
            ->orderByRaw("UPPER(BTRIM(COALESCE(t.nomina, '')))")
            ->first();

        return $row ? self::formatClues($row) : null;
    }

    public static function makeCluesCatalogKey(object $row): string
    {
        return base64_encode(json_encode([
            'clave_clues' => self::norm($row->clave_clues ?? ''),
            'descripcion_clues' => self::norm($row->descripcion_clues ?? ''),
            'nomina' => self::norm($row->nomina ?? ''),
            'entidad' => self::norm($row->entidad ?? ''),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function decodeCluesCatalogKey(?string $catalogKey): ?array
    {
        $catalogKey = trim((string) $catalogKey);

        if ($catalogKey === '') {
            return null;
        }

        $json = base64_decode($catalogKey, true);

        if ($json === false) {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return null;
        }

        $required = ['clave_clues', 'descripcion_clues', 'nomina', 'entidad'];
        $normalized = [];

        foreach ($required as $key) {
            if (! array_key_exists($key, $decoded)) {
                return null;
            }

            $normalized[$key] = self::norm($decoded[$key]);
        }

        if ($normalized['clave_clues'] === '' || $normalized['descripcion_clues'] === '') {
            return null;
        }

        return $normalized;
    }

    public static function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private static function cluesBaseQuery()
    {
        return DB::table('public.tmp_clues as t')
            ->leftJoin('administracion.cat_clues as ac', function ($join) {
                $join->on(
                    DB::raw('UPPER(BTRIM(ac.clues))'),
                    '=',
                    DB::raw('UPPER(BTRIM(t.clave_clues))')
                )->where('ac.activo', true);
            })
            ->selectRaw("
                UPPER(BTRIM(t.clave_clues)) as clave_clues,
                UPPER(BTRIM(COALESCE(NULLIF(BTRIM(t.descripcion_clues), ''), t.clave_clues))) as descripcion_clues,
                UPPER(BTRIM(COALESCE(t.nomina, ''))) as nomina,
                UPPER(BTRIM(COALESCE(t.entidad, ''))) as entidad,
                MAX(ac.id_clues) as id_clues
            ")
            ->whereNotNull('t.clave_clues')
            ->whereRaw("BTRIM(t.clave_clues) <> ''")
            ->whereRaw("UPPER(BTRIM(t.clave_clues)) <> '0'")
            ->whereRaw("BTRIM(COALESCE(t.descripcion_clues, '')) <> ''")
            ->groupByRaw("
                UPPER(BTRIM(t.clave_clues)),
                UPPER(BTRIM(COALESCE(NULLIF(BTRIM(t.descripcion_clues), ''), t.clave_clues))),
                UPPER(BTRIM(COALESCE(t.nomina, ''))),
                UPPER(BTRIM(COALESCE(t.entidad, '')))
            ");
    }

    private static function formatPuesto(object $row): object
    {
        $row->codigo_puesto = self::norm($row->codigo_puesto ?? '');
        $row->puesto = self::norm($row->puesto ?? '');
        $row->nivel = self::norm($row->nivel ?? '');
        $row->estatus = self::norm($row->estatus ?? '');
        $row->label = trim($row->puesto . ' - ' . $row->codigo_puesto);

        return $row;
    }

    private static function formatClues(object $row): object
    {
        $row->clave_clues = self::norm($row->clave_clues ?? '');
        $row->descripcion_clues = self::norm($row->descripcion_clues ?? '');
        $row->nomina = self::norm($row->nomina ?? '');
        $row->entidad = self::norm($row->entidad ?? '');
        $row->catalog_key = self::makeCluesCatalogKey($row);
        $row->label = trim(sprintf(
            '%s - %s%s',
            $row->descripcion_clues,
            $row->clave_clues,
            ($row->entidad !== '' || $row->nomina !== '')
                ? ' (' . trim($row->entidad . ' / ' . $row->nomina, ' /') . ')'
                : ''
        ));

        return $row;
    }
}
