<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class PacVisibility
{
    /**
     * Aplica reglas de visibilidad según rol del usuario.
     *
     * Reglas:
     * - admin_oc, supervisor_oc => ven todo (no filtra)
     * - revisor_est, supervisor_est => filtra por:
     *      id_entidad = user.id_entidad
     *      id_tipo_nomina = user.id_tipo_nomina
     *      y si es HRAES => además id_clues = user.id_clues
     *
     * Nota práctica:
     * Para detectar HRAES sin depender del catálogo, usamos:
     *  - Si el usuario tiene id_clues asignado => aplicamos el filtro por id_clues.
     * (Esto cumple tu regla de HRAES y evita depender del nombre de columna del catálogo.)
     */
    public static function apply(Builder $query, User $user, string $tableOrAlias = 'public.a2_acciones_capacitacion'): Builder
    {
        // Centrales ven todo
        if ($user->hasAnyRole(['admin_oc', 'supervisor_oc'])) {
            return $query;
        }

        // Solo operativos deben llegar aquí
        if (! $user->hasAnyRole(['revisor_est', 'supervisor_est'])) {
            // Si llega un rol raro, por seguridad filtramos a nada
            return $query->whereRaw('1=0');
        }

        // Validaciones mínimas (si no trae datos, mejor bloquear)
        if (empty($user->id_entidad) || empty($user->id_tipo_nomina)) {
            return $query->whereRaw('1=0');
        }

        // Normaliza alias: si te pasan "cap" o "public.a2_acciones_capacitacion"
        $t = Str::contains($tableOrAlias, '.') || Str::contains($tableOrAlias, ' ')
            ? $tableOrAlias
            : $tableOrAlias;

        $query->where("{$t}.id_entidad", (int) $user->id_entidad)
              ->where("{$t}.id_tipo_nomina", (int) $user->id_tipo_nomina);

        // Regla extra para HRAES: además por id_clues
        // (asumimos que si el usuario tiene id_clues asignado aplica esta restricción)
        if (! empty($user->id_clues)) {
            $query->where("{$t}.id_clues", (int) $user->id_clues);
        }

        return $query;
    }
}