<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ConstanciaVisibilityByName
{
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

    public static function resolveScope(int $userId): array
    {
        $empty = [
            'roles'           => [],
            'is_allowed_role' => false,
            'is_admin_global' => false,
            'entidad'         => '',
            'tipo_nomina'     => '',
            'clues'           => '',
            'requires_clues'  => false,
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $userRow = DB::table('administracion.users as u')
            ->leftJoin('administracion.cat_entidad as ce', 'ce.id_entidad', '=', 'u.id_entidad')
            ->leftJoin('administracion.cat_tipo_nomina as ctn', 'ctn.id_tipo_nomina', '=', 'u.id_tipo_nomina')
            ->leftJoin('administracion.cat_clues as cc', 'cc.id_clues', '=', 'u.id_clues')
            ->where('u.id', $userId)
            ->select([
                DB::raw("COALESCE(ce.nombre, '') as entidad_nombre"),
                DB::raw("COALESCE(ctn.codigo, '') as tipo_nomina_codigo"),
                DB::raw("COALESCE(cc.clues, '') as clues_codigo"),
            ])
            ->first();

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
            'SUPERVISOR_OC',
            'REVISOR_EST',
            'SUPERVISOR_EST',
        ];

        $isAllowedRole = count(array_intersect($roles, $allowedRoles)) > 0;
        $isAdminGlobal = in_array('ADMIN_OC', $roles, true);

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
            'requires_clues'  => $requiresClues,
        ];
    }

    private static function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }
}