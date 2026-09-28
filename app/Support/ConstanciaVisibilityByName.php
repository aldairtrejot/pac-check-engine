<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ConstanciaVisibilityByName
{
    private static array $columnsCache = [];

    /**
     * CLUES que solo puede ver ADMIN_OC.
     */
    private const ADMIN_ONLY_CLUES = [
        'DFIMB000014',
    ];

    /**
     * Reglas:
     * - ADMIN_OC: ve absolutamente todo.
     * - SUPERVISOR_OC / REVISOR_EST / SUPERVISOR_EST:
     *   filtrados por entidad + tipo_nomina.
     * - Si el tipo de nómina contiene HRAES, además exige CLUES.
     * - Si el usuario no tiene datos válidos de segmentación, no ve registros.
     */
    public static function apply(Builder $query, $user, string $constAlias = 'c'): void
    {
        $userId = $user ? (int) $user->id : 0;
        $scope = self::resolveScope($userId);

        if (! $scope['is_allowed_role']) {
            $query->whereRaw('1 = 0');
            return;
        }

        // ✅ ADMINISTRADOR VE TODO
        if ($scope['is_admin_global']) {
            $idAdscripcionScope = (int) ($scope['id_adscripcion_scope'] ?? 0);

            if ($idAdscripcionScope > 0) {
                self::applyAdminAdscripcionScope($query, $idAdscripcionScope, $constAlias);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CLUES EXCLUSIVAS DE ADMIN
        |--------------------------------------------------------------------------
        | Como ADMIN_OC ya hizo return arriba, esta regla solo aplica a roles
        | filtrados como supervisor/revisor.
        */
        self::applyAdminOnlyCluesRestriction($query, $constAlias);

        if ($scope['entidad'] === '' || $scope['tipo_nomina'] === '') {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereRaw(
            "UPPER(BTRIM(COALESCE({$constAlias}.entidad, ''))) = ?",
            [$scope['entidad']]
        );

        $query->whereRaw(
            "UPPER(BTRIM(COALESCE({$constAlias}.tipo_nomina, ''))) = ?",
            [$scope['tipo_nomina']]
        );

        if ($scope['requires_clues']) {
            if ($scope['clues'] === '') {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereRaw(
                "UPPER(BTRIM(COALESCE({$constAlias}.clues, ''))) = ?",
                [$scope['clues']]
            );
        }
    }

    private static function applyAdminOnlyCluesRestriction(Builder $query, string $constAlias = 'c'): void
    {
        $restrictedCodes = array_values(array_unique(array_filter(array_map(
            fn ($value) => self::norm($value),
            self::ADMIN_ONLY_CLUES
        ))));

        if (empty($restrictedCodes)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($restrictedCodes), '?'));

        $query->whereRaw(
            "UPPER(BTRIM(COALESCE({$constAlias}.clues::text, ''))) NOT IN ({$placeholders})",
            $restrictedCodes
        );
    }

    private static function applyAdminAdscripcionScope(
        Builder $query,
        int $idAdscripcionScope,
        string $constAlias = 'c'
    ): void {
        if ($idAdscripcionScope <= 0) {
            return;
        }

        if (self::columnExists('public', 'tbl_constancias', 'id_adscripcion')) {
            $query->where("{$constAlias}.id_adscripcion", '=', $idAdscripcionScope);
            return;
        }

        if (
            ! self::columnExists('public', 'tbl_constancias', 'id_puesto')
            || ! self::columnExists('public', 'tbl_constancias', 'curp')
            || ! self::columnExists('public', 'a2_acciones_capacitacion', 'id_puesto')
            || ! self::columnExists('public', 'a2_acciones_capacitacion', 'curp')
            || ! self::columnExists('public', 'a2_acciones_capacitacion', 'num_cursos')
        ) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereExists(function ($exists) use ($idAdscripcionScope, $constAlias) {
            $exists->select(DB::raw(1))
                ->from('public.a2_acciones_capacitacion as cap_scope')
                ->whereRaw("
                    cap_scope.id_puesto =
                    CASE
                        WHEN BTRIM(COALESCE({$constAlias}.id_puesto::text, '')) ~ '^[0-9]+$'
                        THEN BTRIM({$constAlias}.id_puesto::text)::INTEGER
                        ELSE NULL
                    END
                ")
                ->whereRaw("
                    UPPER(BTRIM(COALESCE(cap_scope.curp::text, ''))) =
                    UPPER(BTRIM(COALESCE({$constAlias}.curp::text, '')))
                ")
                ->where('cap_scope.num_cursos', $idAdscripcionScope);
        });
    }

    public static function resolveScope(int $userId): array
    {
        $empty = [
            'roles'           => [],
            'is_allowed_role' => false,
            'is_admin_global' => false,
            'entidad'         => '',
            'tipo_nomina'     => '',
            'clues'           => '',
            'id_adscripcion_scope' => null,
            'requires_clues'  => false,
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $userQuery = DB::table('administracion.users as u')
            ->leftJoin('administracion.cat_entidad as ce', 'ce.id_entidad', '=', 'u.id_entidad')
            ->leftJoin('administracion.cat_tipo_nomina as ctn', 'ctn.id_tipo_nomina', '=', 'u.id_tipo_nomina')
            ->leftJoin('administracion.cat_clues as cc', 'cc.id_clues', '=', 'u.id_clues');

        if (self::tableExists('public', 'cat_clues_bi')) {
            $userQuery->leftJoin('public.cat_clues_bi as cbi_id', function ($join) {
                $join->on(
                    'u.id_clues',
                    '=',
                    DB::raw("
                        CASE
                            WHEN BTRIM(COALESCE(cbi_id.idcat, '')) ~ '^[0-9]+$'
                            THEN BTRIM(cbi_id.idcat)::BIGINT
                            ELSE NULL
                        END
                    ")
                );
            });
        }

        $userQuery->where('u.id', $userId)
            ->select([
                DB::raw("COALESCE(ce.nombre, '') as entidad_nombre"),
                DB::raw("COALESCE(ctn.codigo, '') as tipo_nomina_codigo"),
            ]);

        if (self::columnExists('administracion', 'users', 'id_adscripcion_scope')) {
            $userQuery->addSelect('u.id_adscripcion_scope');
        } else {
            $userQuery->addSelect(DB::raw('NULL::INTEGER as id_adscripcion_scope'));
        }

        if (self::tableExists('public', 'cat_clues_bi')) {
            $userQuery->addSelect(DB::raw("
                COALESCE(
                    NULLIF(BTRIM(cc.clues), ''),
                    NULLIF(BTRIM(cbi_id.clave_clues), ''),
                    ''
                ) as clues_codigo
            "));
        } else {
            $userQuery->addSelect(DB::raw("COALESCE(cc.clues, '') as clues_codigo"));
        }

        $userRow = $userQuery->first();

        if (! $userRow) {
            return $empty;
        }

        $roles = DB::table('administracion.user_roles as ur')
            ->join('administracion.roles as r', 'r.id', '=', 'ur.role_id')
            ->where('ur.user_id', $userId)
            ->where('r.is_active', true)
            ->pluck('r.code')
            ->map(fn ($v) => self::norm($v))
            ->unique()
            ->values()
            ->all();

        $allowedRoles = [
            'ADMIN_OC',
            'ADMIN',
            'SUPERVISOR_OC',
            'REVISOR_EST',
            'SUPERVISOR_EST',
        ];

        $isAllowedRole = count(array_intersect($roles, $allowedRoles)) > 0;
        $isAdminGlobal = in_array('ADMIN_OC', $roles, true) || in_array('ADMIN', $roles, true);

        $entidad = self::norm($userRow->entidad_nombre ?? '');
        $tipoNomina = self::norm($userRow->tipo_nomina_codigo ?? '');
        $clues = self::norm($userRow->clues_codigo ?? '');

        $requiresClues = str_contains($tipoNomina, 'HRAES');

        return [
            'roles'           => $roles,
            'is_allowed_role' => $isAllowedRole,
            'is_admin_global' => $isAdminGlobal,
            'entidad'         => $entidad,
            'tipo_nomina'     => $tipoNomina,
            'clues'           => $clues,
            'id_adscripcion_scope' => ! empty($userRow->id_adscripcion_scope)
                ? (int) $userRow->id_adscripcion_scope
                : null,
            'requires_clues'  => $requiresClues,
        ];
    }

    private static function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private static function tableExists(string $schema, string $table): bool
    {
        try {
            return DB::table('information_schema.tables')
                ->where('table_schema', $schema)
                ->where('table_name', $table)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function columnExists(string $schema, string $table, string $column): bool
    {
        return in_array($column, self::columnsFor($schema, $table), true);
    }

    private static function columnsFor(string $schema, string $table): array
    {
        $key = $schema . '.' . $table;

        if (! isset(self::$columnsCache[$key])) {
            try {
                self::$columnsCache[$key] = DB::table('information_schema.columns')
                    ->where('table_schema', $schema)
                    ->where('table_name', $table)
                    ->orderBy('ordinal_position')
                    ->pluck('column_name')
                    ->map(fn ($column) => (string) $column)
                    ->all();
            } catch (\Throwable $e) {
                self::$columnsCache[$key] = [];
            }
        }

        return self::$columnsCache[$key];
    }
}
