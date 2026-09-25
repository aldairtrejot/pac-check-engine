<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmpleadoCatalogs
{
    private static ?bool $catCluesBiAvailable = null;
    private static array $tableExistsCache = [];

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

    public static function valPlantillaOptions(): Collection
    {
        return DB::table('public.a2_acciones_capacitacion')
            ->whereNotNull('val_plantilla')
            ->whereRaw("BTRIM(COALESCE(val_plantilla::text, '')) <> ''")
            ->selectRaw("UPPER(BTRIM(val_plantilla::text)) as value")
            ->groupByRaw("UPPER(BTRIM(val_plantilla::text))")
            ->orderBy('value')
            ->pluck('value')
            ->map(fn ($value) => self::norm($value))
            ->filter(fn ($value) => $value !== '')
            ->values();
    }

    public static function nominaDosOptions(): Collection
    {
        if (! self::capacitacionColumnExists('nomina_dos')) {
            return collect();
        }

        return DB::table('public.a2_acciones_capacitacion')
            ->whereNotNull('nomina_dos')
            ->whereRaw("BTRIM(COALESCE(nomina_dos::text, '')) <> ''")
            ->selectRaw("UPPER(BTRIM(nomina_dos::text)) as value")
            ->groupByRaw("UPPER(BTRIM(nomina_dos::text))")
            ->orderBy('value')
            ->pluck('value')
            ->map(fn ($value) => self::norm($value))
            ->filter(fn ($value) => $value !== '')
            ->values();
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
            ->orderBy('c.entidad')
            ->orderBy('c.descripcion_clues')
            ->orderBy('c.clave_clues')
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
                    "UPPER(public.unaccent(c.clave_clues)) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(c.descripcion_clues, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(c.entidad, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(c.nomina, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(c.nivel_atencion, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(c.clues_completa, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                )
                ->orWhereRaw(
                    "UPPER(public.unaccent(COALESCE(c.nuevas_clues, ''))) LIKE UPPER(public.unaccent(?))",
                    [$term]
                );
            });
        }

        return $query
            ->orderBy('c.entidad')
            ->orderBy('c.descripcion_clues')
            ->orderBy('c.clave_clues')
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

        $query = self::cluesBaseQuery()
            ->whereRaw("UPPER(BTRIM(c.clave_clues)) = ?", [$decoded['clave_clues']]);

        if (($decoded['idcat'] ?? '') !== '') {
            $query->whereRaw("UPPER(BTRIM(COALESCE(c.idcat, ''))) = ?", [$decoded['idcat']]);
        } else {
            $query->whereRaw("UPPER(BTRIM(COALESCE(c.descripcion_clues, ''))) = ?", [$decoded['descripcion_clues']])
                ->whereRaw("UPPER(BTRIM(COALESCE(c.entidad, ''))) = ?", [$decoded['entidad']]);

            if (($decoded['nomina'] ?? '') !== '') {
                $query->whereRaw("UPPER(BTRIM(COALESCE(c.nomina, ''))) = ?", [$decoded['nomina']]);
            }
        }

        $row = $query->first();

        if (! $row) {
            return self::findCluesByClave($decoded['clave_clues']);
        }

        return $row ? self::formatClues($row) : null;
    }

    public static function findCluesByClave(?string $claveClues): ?object
    {
        $claveClues = self::norm($claveClues);

        if ($claveClues === '') {
            return null;
        }

        $row = self::cluesBaseQuery()
            ->whereRaw("UPPER(BTRIM(c.clave_clues)) = ?", [$claveClues])
            ->orderBy('c.entidad')
            ->orderBy('c.nomina')
            ->first();

        return $row ? self::formatClues($row) : null;
    }

    public static function manualCluesFromValues(
        ?string $claveClues,
        ?string $descripcionClues,
        ?string $nomina = null,
        ?string $entidad = null,
        ?string $nivelAtencion = null
    ): ?object {
        $claveClues = self::norm($claveClues);
        $descripcionClues = self::norm($descripcionClues);

        if ($claveClues === '' || $descripcionClues === '') {
            return null;
        }

        return self::formatClues((object) [
            'idcat' => '',
            'clave_clues' => $claveClues,
            'descripcion_clues' => $descripcionClues,
            'nomina' => self::norm($nomina),
            'entidad' => self::norm($entidad),
            'id_clues' => null,
            'nivel_atencion' => self::norm($nivelAtencion),
            'nuevas_clues' => '',
            'clues_completa' => "{$claveClues}-{$descripcionClues}",
        ]);
    }

    public static function ensureManualClues(
        ?string $claveClues,
        ?string $descripcionClues,
        ?string $nomina = null,
        ?string $entidad = null,
        ?string $nivelAtencion = null
    ): ?object {
        $manual = self::manualCluesFromValues($claveClues, $descripcionClues, $nomina, $entidad, $nivelAtencion);

        if (! $manual) {
            return null;
        }

        $existing = self::findCluesByClave($manual->clave_clues);

        if ($existing) {
            return $existing;
        }

        if (! self::tableExists('public', 'cat_clues_bi')) {
            return $manual;
        }

        DB::statement('SELECT pg_advisory_xact_lock(2026092402)');

        $existing = self::findCluesByClave($manual->clave_clues);

        if ($existing) {
            return $existing;
        }

        $maxIdCat = DB::table('public.cat_clues_bi')
            ->whereRaw("BTRIM(COALESCE(idcat, '')) ~ '^[0-9]+$'")
            ->selectRaw('MAX(BTRIM(idcat)::BIGINT) as max_id')
            ->value('max_id');

        $idcat = (string) (((int) ($maxIdCat ?? 0)) + 1);

        DB::table('public.cat_clues_bi')->insert([
            'idcat' => $idcat,
            'clave_clues' => $manual->clave_clues,
            'nombre_comercial' => $manual->descripcion_clues,
            'pais' => 'MEXICO',
            'zona_pago' => $manual->entidad !== '' ? $manual->entidad : null,
            'nivel_atencion' => $manual->nivel_atencion !== '' ? $manual->nivel_atencion : null,
            'clues_completa' => "{$manual->clave_clues}-{$manual->descripcion_clues}",
            'estatus' => 'NORMAL',
        ]);

        return self::findCluesByClave($manual->clave_clues) ?: self::manualCluesFromValues(
            $manual->clave_clues,
            $manual->descripcion_clues,
            $manual->nomina,
            $manual->entidad,
            $manual->nivel_atencion
        );
    }

    public static function makeCluesCatalogKey(object $row): string
    {
        return base64_encode(json_encode([
            'idcat' => self::norm($row->idcat ?? ''),
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

        $normalized['idcat'] = array_key_exists('idcat', $decoded)
            ? self::norm($decoded['idcat'])
            : '';

        if ($normalized['clave_clues'] === '' || $normalized['descripcion_clues'] === '') {
            return null;
        }

        return $normalized;
    }

    public static function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private static function capacitacionColumnExists(string $column): bool
    {
        try {
            return DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', 'a2_acciones_capacitacion')
                ->where('column_name', $column)
                ->exists();
        } catch (\Throwable $th) {
            return false;
        }
    }

    private static function cluesBaseQuery()
    {
        $source = self::useCatCluesBi()
            ? self::catCluesBiSourceQuery()
            : self::tmpCluesSourceQuery();

        return DB::query()->fromSub($source, 'c');
    }

    private static function catCluesBiSourceQuery()
    {
        $descriptionExpr = self::catCluesBiDescriptionExpr();
        $entityExpr = self::catCluesBiEntityExpr();
        $hasCatClues = self::tableExists('administracion', 'cat_clues');
        $hasTmpClues = self::tableExists('public', 'tmp_clues');

        $query = DB::table('public.cat_clues_bi as t');

        if ($hasCatClues) {
            $query->leftJoin('administracion.cat_clues as ac', function ($join) {
                $join->on(
                    DB::raw('UPPER(BTRIM(ac.clues))'),
                    '=',
                    DB::raw('UPPER(BTRIM(t.clave_clues))')
                )->where('ac.activo', true);
            });
        }

        if ($hasTmpClues) {
            $query->leftJoinSub(self::tmpCluesNominaSubquery(), 'tc', function ($join) {
                $join->on(
                    'tc.clave_clues',
                    '=',
                    DB::raw('UPPER(BTRIM(t.clave_clues))')
                );
            });
        }

        $idCluesSelect = $hasCatClues ? 'MAX(ac.id_clues)' : 'NULL::integer';
        $nominaSelect = $hasTmpClues ? "UPPER(BTRIM(COALESCE(MAX(tc.nomina), '')))" : "''";

        return $query
            ->selectRaw("
                UPPER(BTRIM(t.clave_clues)) as clave_clues,
                {$descriptionExpr} as descripcion_clues,
                {$nominaSelect} as nomina,
                {$entityExpr} as entidad,
                {$idCluesSelect} as id_clues,
                UPPER(BTRIM(COALESCE(t.idcat, ''))) as idcat,
                UPPER(BTRIM(COALESCE(t.nivel_atencion, ''))) as nivel_atencion,
                UPPER(BTRIM(COALESCE(t.nuevas_clues, ''))) as nuevas_clues,
                UPPER(BTRIM(COALESCE(t.clues_completa, ''))) as clues_completa
            ")
            ->whereNotNull('t.clave_clues')
            ->whereRaw("BTRIM(t.clave_clues) <> ''")
            ->whereRaw("UPPER(BTRIM(t.clave_clues)) <> '0'")
            ->whereRaw("BTRIM(COALESCE(t.nombre_comercial, t.nombre_comercial_equivalencia, t.clues_completa, t.clave_clues)) <> ''")
            ->groupByRaw("
                UPPER(BTRIM(t.clave_clues)),
                {$descriptionExpr},
                {$entityExpr},
                UPPER(BTRIM(COALESCE(t.idcat, ''))),
                UPPER(BTRIM(COALESCE(t.nivel_atencion, ''))),
                UPPER(BTRIM(COALESCE(t.nuevas_clues, ''))),
                UPPER(BTRIM(COALESCE(t.clues_completa, '')))
            ");
    }

    private static function tmpCluesSourceQuery()
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
                MAX(ac.id_clues) as id_clues,
                '' as idcat,
                '' as nivel_atencion,
                '' as nuevas_clues,
                '' as clues_completa
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

    private static function tmpCluesNominaSubquery()
    {
        return DB::table('public.tmp_clues')
            ->selectRaw("
                UPPER(BTRIM(clave_clues)) as clave_clues,
                MAX(UPPER(BTRIM(COALESCE(nomina, '')))) as nomina
            ")
            ->whereNotNull('clave_clues')
            ->whereRaw("BTRIM(clave_clues) <> ''")
            ->groupByRaw("UPPER(BTRIM(clave_clues))");
    }

    private static function catCluesBiDescriptionExpr(): string
    {
        return "
            UPPER(BTRIM(COALESCE(
                NULLIF(BTRIM(t.nombre_comercial), ''),
                NULLIF(BTRIM(t.nombre_comercial_equivalencia), ''),
                NULLIF(BTRIM(REGEXP_REPLACE(COALESCE(t.clues_completa, ''), '^[^-]+-', '')), ''),
                t.clave_clues
            )))
        ";
    }

    private static function catCluesBiEntityExpr(): string
    {
        return "
            UPPER(BTRIM(COALESCE(
                NULLIF(BTRIM(t.zona_pago), ''),
                NULLIF(BTRIM(t.abreviatura), ''),
                ''
            )))
        ";
    }

    private static function useCatCluesBi(): bool
    {
        if (self::$catCluesBiAvailable !== null) {
            return self::$catCluesBiAvailable;
        }

        try {
            self::$catCluesBiAvailable = self::tableExists('public', 'cat_clues_bi')
                && DB::table('public.cat_clues_bi')
                    ->whereNotNull('clave_clues')
                    ->whereRaw("BTRIM(clave_clues) <> ''")
                    ->exists();
        } catch (\Throwable $th) {
            self::$catCluesBiAvailable = false;
        }

        return self::$catCluesBiAvailable;
    }

    private static function tableExists(string $schema, string $table): bool
    {
        $key = "{$schema}.{$table}";

        if (! array_key_exists($key, self::$tableExistsCache)) {
            try {
                self::$tableExistsCache[$key] = DB::table('information_schema.tables')
                    ->where('table_schema', $schema)
                    ->where('table_name', $table)
                    ->exists();
            } catch (\Throwable $th) {
                self::$tableExistsCache[$key] = false;
            }
        }

        return self::$tableExistsCache[$key];
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
        $row->idcat = self::norm($row->idcat ?? '');
        $row->clave_clues = self::norm($row->clave_clues ?? '');
        $row->descripcion_clues = self::norm($row->descripcion_clues ?? '');
        $row->nomina = self::norm($row->nomina ?? '');
        $row->entidad = self::norm($row->entidad ?? '');
        $row->nivel_atencion = self::norm($row->nivel_atencion ?? '');
        $row->nuevas_clues = self::norm($row->nuevas_clues ?? '');
        $row->clues_completa = self::norm($row->clues_completa ?? '');
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
