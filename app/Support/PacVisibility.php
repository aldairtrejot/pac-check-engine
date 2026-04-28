<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PacVisibility
{
    private static array $columnsCache = [];

    public static function apply(
        Builder $query,
        $user,
        string $capAlias = 'public.a2_acciones_capacitacion',
        string $capTableQualified = 'public.a2_acciones_capacitacion'
    ): void {
        if (! $user) {
            $query->whereRaw('1 = 0');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | SOLO ADMIN GLOBAL VE TODO
        |--------------------------------------------------------------------------
        | ADMIN_OC / ADMIN pueden ver todo.
        | SUPERVISOR_OC, REVISOR_EST y SUPERVISOR_EST deben filtrarse.
        */
        if (self::isAdminGlobal($user)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ROLES PERMITIDOS CON FILTRO
        |--------------------------------------------------------------------------
        */
        if (! self::hasAnyRole($user, [
            'SUPERVISOR_OC',
            'REVISOR_EST',
            'SUPERVISOR_EST',
        ])) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (empty($user->id_entidad) || empty($user->id_tipo_nomina)) {
            $query->whereRaw('1 = 0');
            return;
        }

        [$schemaCap, $tableCap] = self::splitQualified($capTableQualified);

        $capEntidadCol = self::firstExistingColumn($schemaCap, $tableCap, [
            'id_entidad',
            'entidad',
        ]);

        $capNominaCol = self::firstExistingColumn($schemaCap, $tableCap, [
            'id_tipo_nomina',
            'nomina',
        ]);

        $capCluesCol = self::firstExistingColumn($schemaCap, $tableCap, [
            'id_clues',
            'clave_clues',
            'clues',
        ]);

        if (! $capEntidadCol || ! $capNominaCol) {
            $query->whereRaw('1 = 0');
            return;
        }

        $cap = trim($capAlias);

        /*
        |--------------------------------------------------------------------------
        | ENTIDAD
        |--------------------------------------------------------------------------
        */
        if ($capEntidadCol === 'id_entidad') {
            $query->where("{$cap}.id_entidad", '=', (int) $user->id_entidad);
        } else {
            $entidadLabels = self::lookupLabels(
                'administracion.cat_entidad',
                'id_entidad',
                (int) $user->id_entidad,
                [
                    'nombre',
                    'abreviatura',
                    'entidad',
                    'descripcion',
                    'desc_entidad',
                ]
            );

            if (empty($entidadLabels)) {
                $query->whereRaw('1 = 0');
                return;
            }

            self::whereNormalizedIn($query, "{$cap}.entidad", $entidadLabels);
        }

        /*
        |--------------------------------------------------------------------------
        | NÓMINA
        |--------------------------------------------------------------------------
        */
        $nominaLabels = [];

        if ($capNominaCol === 'id_tipo_nomina') {
            $query->where("{$cap}.id_tipo_nomina", '=', (int) $user->id_tipo_nomina);
        } else {
            $nominaLabels = self::lookupLabels(
                'administracion.cat_tipo_nomina',
                'id_tipo_nomina',
                (int) $user->id_tipo_nomina,
                [
                    'codigo',
                    'nombre',
                    'tipo_nomina',
                    'nomina',
                    'descripcion',
                ]
            );

            if (empty($nominaLabels)) {
                $query->whereRaw('1 = 0');
                return;
            }

            self::whereNormalizedIn($query, "{$cap}.nomina", $nominaLabels);
        }

        /*
        |--------------------------------------------------------------------------
        | CLUES
        |--------------------------------------------------------------------------
        | Si es HRAES, CLUES es obligatorio.
        | Si el usuario trae id_clues, también se filtra por CLUES.
        */
        $requiresClues = self::labelsContain($nominaLabels, 'HRAES');

        if ($requiresClues && empty($user->id_clues)) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (! empty($user->id_clues)) {
            if (! $capCluesCol) {
                $query->whereRaw('1 = 0');
                return;
            }

            if ($capCluesCol === 'id_clues') {
                $query->where("{$cap}.id_clues", '=', (int) $user->id_clues);
                return;
            }

            $cluesLabels = self::lookupLabels(
                'administracion.cat_clues',
                'id_clues',
                (int) $user->id_clues,
                [
                    'clues',
                    'clave_clues',
                    'clave',
                    'descripcion',
                    'nombre',
                ]
            );

            if (empty($cluesLabels)) {
                $query->whereRaw('1 = 0');
                return;
            }

            if ($capCluesCol === 'clave_clues') {
                self::whereNormalizedIn($query, "{$cap}.clave_clues", $cluesLabels);
            } else {
                self::whereNormalizedIn($query, "{$cap}.clues", $cluesLabels);
            }
        }
    }

    private static function isAdminGlobal($user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            'ADMIN_OC',
            'ADMIN',
            'admin_oc',
            'admin',
        ])) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('ADMIN_OC')
                || $user->hasRole('ADMIN')
                || $user->hasRole('admin_oc')
                || $user->hasRole('admin');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (isset($user->rol_id) && (int) $user->rol_id === 1) {
            return true;
        }

        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        return false;
    }

    private static function hasAnyRole($user, array $roles): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles)) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function labelsContain(array $labels, string $needle): bool
    {
        $needle = mb_strtoupper(trim($needle), 'UTF-8');

        foreach ($labels as $label) {
            if (str_contains(mb_strtoupper(trim((string) $label), 'UTF-8'), $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function splitQualified(string $qualified): array
    {
        $qualified = trim($qualified);

        if (str_contains($qualified, '.')) {
            $parts = explode('.', $qualified);

            if (count($parts) === 2) {
                return [$parts[0], $parts[1]];
            }
        }

        return ['public', $qualified];
    }

    private static function columnsFor(string $schema, string $table): array
    {
        $key = $schema . '.' . $table;

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

    private static function firstExistingColumn(string $schema, string $table, array $candidates): ?string
    {
        $cols = self::columnsFor($schema, $table);
        $set = array_flip($cols);

        foreach ($candidates as $candidate) {
            if (isset($set[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    private static function existingColumns(string $schema, string $table, array $candidates): array
    {
        $cols = self::columnsFor($schema, $table);
        $set = array_flip($cols);

        $found = [];

        foreach ($candidates as $candidate) {
            if (isset($set[$candidate])) {
                $found[] = $candidate;
            }
        }

        return $found;
    }

    private static function lookupLabels(
        string $qualifiedTable,
        string $idCol,
        int $idVal,
        array $labelCandidates
    ): array {
        [$schema, $table] = self::splitQualified($qualifiedTable);

        $labelCols = self::existingColumns($schema, $table, $labelCandidates);

        if (empty($labelCols)) {
            return [];
        }

        $row = DB::table($qualifiedTable)
            ->select($labelCols)
            ->where($idCol, $idVal)
            ->first();

        if (! $row) {
            return [];
        }

        $labels = [];

        foreach ($labelCols as $col) {
            if (isset($row->{$col}) && trim((string) $row->{$col}) !== '') {
                $labels[] = (string) $row->{$col};
            }
        }

        return self::uniqueNormalizedLabels($labels);
    }

    private static function uniqueNormalizedLabels(array $labels): array
    {
        $normalized = [];

        foreach ($labels as $label) {
            $value = mb_strtoupper(trim((string) $label), 'UTF-8');

            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private static function whereNormalizedIn(Builder $query, string $column, array $values): void
    {
        $values = self::uniqueNormalizedLabels($values);

        if (empty($values)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));

        $query->whereRaw(
            "UPPER(TRIM(COALESCE({$column}, ''))) IN ({$placeholders})",
            $values
        );
    }
}