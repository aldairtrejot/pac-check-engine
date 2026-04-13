<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ConstanciaVisibility
{
    private static array $columnsCache = [];
    private static array $tableExistsCache = [];

    /**
     * Aplica visibilidad por entidad / tipo de nómina / CLUES.
     *
     * Regla:
     * - Si el usuario no trae ningún scope, no ve nada.
     * - Si el usuario es HRAES y no trae CLUES, no ve nada.
     * - Si trae CLUES, además se filtra por CLUES.
     */
    public static function apply(Builder $query, $user, string $capAlias = 'cap'): void
    {
        if (! $user) {
            $query->whereRaw('1=0');
            return;
        }

        $scope = self::resolveUserScope($user);

        if (! $scope['has_any_scope']) {
            $query->whereRaw('1=0');
            return;
        }

        if ($scope['requires_clues'] && empty($scope['clues_ids']) && empty($scope['clues_codes'])) {
            $query->whereRaw('1=0');
            return;
        }

        if (! empty($scope['entidad_ids'])) {
            $query->whereIn("{$capAlias}.scope_id_entidad", $scope['entidad_ids']);
        }

        if (! empty($scope['nomina_ids'])) {
            $query->whereIn("{$capAlias}.scope_id_tipo_nomina", $scope['nomina_ids']);
        }

        if (! empty($scope['clues_ids']) || ! empty($scope['clues_codes'])) {
            $cluesIds   = $scope['clues_ids'];
            $cluesCodes = $scope['clues_codes'];

            $query->where(function ($w) use ($capAlias, $cluesIds, $cluesCodes) {
                if (! empty($cluesIds)) {
                    $w->whereIn("{$capAlias}.scope_id_clues", $cluesIds);
                }

                if (! empty($cluesCodes)) {
                    $placeholders = implode(',', array_fill(0, count($cluesCodes), '?'));
                    $sql = "UPPER(TRIM(COALESCE({$capAlias}.scope_clues, ''))) IN ({$placeholders})";

                    if (! empty($cluesIds)) {
                        $w->orWhereRaw($sql, $cluesCodes);
                    } else {
                        $w->whereRaw($sql, $cluesCodes);
                    }
                }
            });
        }
    }

    /**
     * Subconsulta única de capacitación:
     * - 1 fila por id_puesto + curp
     * - nombre de persona
     * - scope normalizado:
     *   scope_id_entidad
     *   scope_id_tipo_nomina
     *   scope_id_clues
     *   scope_clues
     *
     * Si a2_acciones_capacitacion no trae directamente entidad/nómina/CLUES,
     * intenta derivarlo desde administracion.cat_clues.
     */
    public static function latestCapacitacionSubquery(string $alias = 'cap')
    {
        $capCols = self::columnsFor('public', 'a2_acciones_capacitacion');

        $capIdCluesCol   = self::firstExisting($capCols, ['id_clues', 'id_tbl_clues']);
        $capCluesCodeCol = self::firstExisting($capCols, ['clues', 'clave_clues', 'clave']);

        $hasCatClues = self::tableExists('administracion', 'cat_clues');

        $joinCatClues = '';
        if ($hasCatClues) {
            if ($capIdCluesCol) {
                $joinCatClues = "LEFT JOIN administracion.cat_clues cc ON cc.id_clues = base.{$capIdCluesCol}";
            } elseif ($capCluesCodeCol) {
                $joinCatClues = "LEFT JOIN administracion.cat_clues cc ON UPPER(TRIM(cc.clues)) = UPPER(TRIM(base.{$capCluesCodeCol}))";
            }
        }

        $selects = [
            'base.id_puesto',
            'base.curp',
            self::selectBaseOrNull($capCols, 'nombre'),
            self::selectBaseOrNull($capCols, 'apellido_paterno'),
            self::selectBaseOrNull($capCols, 'apellido_materno'),
            self::coalesceSelect(
                self::existingBaseExprs($capCols, ['id_entidad', 'id_cat_entidad'], 'base'),
                $hasCatClues ? ['cc.id_entidad'] : [],
                'scope_id_entidad'
            ),
            self::coalesceSelect(
                self::existingBaseExprs($capCols, ['id_tipo_nomina', 'id_cat_tipo_nomina', 'cat_tipo_nomina'], 'base'),
                $hasCatClues ? ['cc.id_tipo_nomina'] : [],
                'scope_id_tipo_nomina'
            ),
            self::coalesceSelect(
                self::existingBaseExprs($capCols, ['id_clues', 'id_tbl_clues'], 'base'),
                $hasCatClues ? ['cc.id_clues'] : [],
                'scope_id_clues'
            ),
            self::coalesceSelect(
                self::existingBaseExprs($capCols, ['clues', 'clave_clues', 'clave'], 'base'),
                $hasCatClues ? ['cc.clues'] : [],
                'scope_clues'
            ),
        ];

        $orderTail = in_array('id_cat', $capCols, true)
            ? 'base.id_cat DESC NULLS LAST'
            : 'base.id_puesto DESC NULLS LAST';

        $sql = "
            (
                SELECT DISTINCT ON (base.id_puesto, UPPER(TRIM(base.curp)))
                    ".implode(",\n                    ", $selects)."
                FROM public.a2_acciones_capacitacion base
                {$joinCatClues}
                WHERE base.id_puesto IS NOT NULL
                  AND base.curp IS NOT NULL
                  AND TRIM(base.curp) <> ''
                ORDER BY base.id_puesto, UPPER(TRIM(base.curp)), {$orderTail}
            ) as {$alias}
        ";

        return DB::raw($sql);
    }

    private static function resolveUserScope($user): array
    {
        $entidadIds = [];
        $nominaIds  = [];
        $cluesIds   = [];
        $cluesCodes = [];

        // ========= 1) sesión =========
        [$sessEntidadIds, ] = self::sessionMixedValues([
            'entidades_permitidas',
            'entity_ids',
        ]);
        $entidadIds = array_merge($entidadIds, $sessEntidadIds);

        [$sessNominaIds, ] = self::sessionMixedValues([
            'tipos_nomina_permitidos',
            'nominas_permitidas',
            'tipo_nomina_permitida',
        ]);
        $nominaIds = array_merge($nominaIds, $sessNominaIds);

        [$sessCluesIds, $sessCluesCodes] = self::sessionMixedValues([
            'clues_permitidas',
            'clues_ids',
            'clues_codes',
            'clues_claves',
        ]);
        $cluesIds   = array_merge($cluesIds, $sessCluesIds);
        $cluesCodes = array_merge($cluesCodes, $sessCluesCodes);

        // ========= 2) administracion.users =========
        $userEntidad = self::firstFilledProperty($user, ['id_entidad', 'id_cat_entidad']);
        if ($userEntidad !== null && $userEntidad !== '') {
            $entidadIds[] = (int) $userEntidad;
        }

        $userNomina = self::firstFilledProperty($user, ['id_tipo_nomina', 'id_cat_tipo_nomina', 'cat_tipo_nomina']);
        if ($userNomina !== null && $userNomina !== '') {
            $nominaIds[] = is_numeric($userNomina) ? (int) $userNomina : $userNomina;
        }

        $userClues = self::firstFilledProperty($user, ['id_clues', 'id_tbl_clues']);
        if ($userClues !== null && $userClues !== '') {
            $cluesIds[] = (int) $userClues;
        }

        // ========= 3) completar desde administracion.cat_clues =========
        if (self::tableExists('administracion', 'cat_clues')) {
            $cluesIds   = self::uniqueInts($cluesIds);
            $cluesCodes = self::uniqueUpperStrings($cluesCodes);

            $ccQuery = DB::table('administracion.cat_clues')
                ->select('id_clues', 'clues', 'id_entidad', 'id_tipo_nomina');

            $hasAnyCatCluesCriteria = false;

            $ccQuery->where(function ($q) use ($cluesIds, $cluesCodes, &$hasAnyCatCluesCriteria) {
                if (! empty($cluesIds)) {
                    $q->orWhereIn('id_clues', $cluesIds);
                    $hasAnyCatCluesCriteria = true;
                }

                if (! empty($cluesCodes)) {
                    foreach ($cluesCodes as $code) {
                        $q->orWhereRaw('UPPER(TRIM(clues)) = ?', [$code]);
                        $hasAnyCatCluesCriteria = true;
                    }
                }
            });

            if ($hasAnyCatCluesCriteria) {
                $ccRows = $ccQuery->get();

                foreach ($ccRows as $row) {
                    if (! empty($row->id_clues)) {
                        $cluesIds[] = (int) $row->id_clues;
                    }

                    if (! empty($row->clues)) {
                        $cluesCodes[] = mb_strtoupper(trim((string) $row->clues), 'UTF-8');
                    }

                    if (! empty($row->id_entidad)) {
                        $entidadIds[] = (int) $row->id_entidad;
                    }

                    if (! empty($row->id_tipo_nomina)) {
                        $nominaIds[] = (int) $row->id_tipo_nomina;
                    }
                }
            }
        }

        $entidadIds = self::uniqueInts($entidadIds);
        $nominaIds  = self::uniqueInts($nominaIds);
        $cluesIds   = self::uniqueInts($cluesIds);
        $cluesCodes = self::uniqueUpperStrings($cluesCodes);

        $requiresClues = self::isUserHraes($nominaIds);

        return [
            'entidad_ids'   => $entidadIds,
            'nomina_ids'    => $nominaIds,
            'clues_ids'     => $cluesIds,
            'clues_codes'   => $cluesCodes,
            'requires_clues'=> $requiresClues,
            'has_any_scope' => ! empty($entidadIds) || ! empty($nominaIds) || ! empty($cluesIds) || ! empty($cluesCodes),
        ];
    }

    private static function isUserHraes(array $nominaIds): bool
    {
        $nominaIds = self::uniqueInts($nominaIds);
        if (empty($nominaIds) || ! self::tableExists('administracion', 'cat_tipo_nomina')) {
            return false;
        }

        try {
            return DB::table('administracion.cat_tipo_nomina')
                ->whereIn('id_tipo_nomina', $nominaIds)
                ->where(function ($q) {
                    $q->whereRaw("UPPER(TRIM(COALESCE(codigo, ''))) LIKE '%HRAES%'")
                      ->orWhereRaw("UPPER(TRIM(COALESCE(nombre, ''))) LIKE '%HRAES%'");
                })
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function sessionMixedValues(array $keys): array
    {
        $ints = [];
        $strings = [];

        foreach ($keys as $key) {
            $value = session($key);

            if ($value === null || $value === '') {
                continue;
            }

            $items = is_array($value) ? $value : [$value];

            foreach ($items as $item) {
                if ($item === null || $item === '') {
                    continue;
                }

                if (is_numeric($item)) {
                    $ints[] = (int) $item;
                } else {
                    $str = trim((string) $item);
                    if ($str !== '') {
                        $strings[] = mb_strtoupper($str, 'UTF-8');
                    }
                }
            }
        }

        return [self::uniqueInts($ints), self::uniqueUpperStrings($strings)];
    }

    private static function firstFilledProperty($object, array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (isset($object->{$candidate}) && $object->{$candidate} !== null && $object->{$candidate} !== '') {
                return $object->{$candidate};
            }
        }

        return null;
    }

    private static function uniqueInts(array $values): array
    {
        $ints = [];

        foreach ($values as $value) {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                continue;
            }

            $ints[] = (int) $value;
        }

        return array_values(array_unique($ints));
    }

    private static function uniqueUpperStrings(array $values): array
    {
        $strings = [];

        foreach ($values as $value) {
            $str = mb_strtoupper(trim((string) $value), 'UTF-8');
            if ($str !== '') {
                $strings[] = $str;
            }
        }

        return array_values(array_unique($strings));
    }

    private static function columnsFor(string $schema, string $table): array
    {
        $key = "{$schema}.{$table}";

        if (! isset(self::$columnsCache[$key])) {
            self::$columnsCache[$key] = DB::table('information_schema.columns')
                ->where('table_schema', $schema)
                ->where('table_name', $table)
                ->orderBy('ordinal_position')
                ->pluck('column_name')
                ->map(fn ($c) => (string) $c)
                ->all();
        }

        return self::$columnsCache[$key];
    }

    private static function tableExists(string $schema, string $table): bool
    {
        $key = "{$schema}.{$table}";

        if (! isset(self::$tableExistsCache[$key])) {
            self::$tableExistsCache[$key] = DB::table('information_schema.tables')
                ->where('table_schema', $schema)
                ->where('table_name', $table)
                ->exists();
        }

        return self::$tableExistsCache[$key];
    }

    private static function firstExisting(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function selectBaseOrNull(array $columns, string $column): string
    {
        return in_array($column, $columns, true)
            ? "base.{$column} AS {$column}"
            : "NULL AS {$column}";
    }

    private static function existingBaseExprs(array $columns, array $candidates, string $prefix = 'base'): array
    {
        $exprs = [];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $exprs[] = "{$prefix}.{$candidate}";
            }
        }

        return $exprs;
    }

    private static function coalesceSelect(array $exprs1, array $exprs2, string $alias): string
    {
        $exprs = array_values(array_filter(array_merge($exprs1, $exprs2)));

        if (empty($exprs)) {
            return "NULL AS {$alias}";
        }

        if (count($exprs) === 1) {
            return "{$exprs[0]} AS {$alias}";
        }

        return 'COALESCE('.implode(', ', $exprs).") AS {$alias}";
    }
}