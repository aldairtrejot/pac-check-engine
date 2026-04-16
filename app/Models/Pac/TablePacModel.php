<?php

namespace App\Models\Pac;

use App\Support\PacVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TablePacModel extends Model
{
    public function list($limit, $offset, $search, $select, $request)
    {
        $user = auth()->user();

        $query = DB::table('public.a2_acciones_empleados')
            ->selectRaw("
                public.a2_acciones_empleados.id_empl_accion AS id,
                public.a2_acciones_capacitacion.nombre AS nombre,
                public.a2_acciones_capacitacion.apellido_paterno || ' ' ||
                public.a2_acciones_capacitacion.apellido_materno AS apellido,
                public.a2_acciones_empleados.curp AS curp,
                public.a1_cat_acciones.nombre_accion AS accion,
                CASE
                    WHEN (
                        public.a2_acciones_empleados.id_cat_estatus IS NOT NULL
                        AND public.a2_acciones_empleados.fecha_ini IS NOT NULL
                        AND public.a2_acciones_empleados.fecha_fin IS NOT NULL
                        AND public.a2_acciones_empleados.id_trimestre IS NOT NULL
                        AND (
                            public.a2_acciones_empleados.id_instancia IS NOT NULL
                            AND TRIM(public.a2_acciones_empleados.id_instancia) <> ''
                        )
                        AND (
                            public.a2_acciones_empleados.id_cat_tematica IS NOT NULL
                            AND TRIM(public.a2_acciones_empleados.id_cat_tematica) <> ''
                        )
                    )
                    THEN 'CONCLUIDO'
                    ELSE 'PENDIENTE'
                END AS atendido
            ")
            ->join(
                'public.a1_cat_acciones',
                'public.a2_acciones_empleados.id_accion',
                '=',
                'public.a1_cat_acciones.id_accion'
            )
            ->join('public.a2_acciones_capacitacion', function ($join) {
                $join->on(
                    DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                    '=',
                    'public.a2_acciones_capacitacion.id_puesto'
                )->whereRaw(
                    'UPPER(TRIM(public.unaccent(public.a2_acciones_empleados.curp))) = UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.curp)))'
                );
            });

        // Ocultar BAJA (3) en la lista: mostramos NULL, 1, 2
        $query->where(function ($q) {
            $q->whereNull('public.a2_acciones_empleados.id_cat_estatus')
              ->orWhereIn('public.a2_acciones_empleados.id_cat_estatus', [1, 2]);
        });

        /**
         * Admin / admin_oc / revisor_est ven igual que admin
         * Los demás sí pasan por visibilidad normal
         */
        if (! $this->isAdminOrRevisorEst($user)) {
            PacVisibility::apply($query, $user, 'public.a2_acciones_capacitacion');
        }

        $this->applySearch($query, $request);

        $countQuery = clone $query;
        $allRow = $countQuery->count();

        $list = $query->orderBy('public.a2_acciones_empleados.curp', 'ASC')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $row = abs(($allRow < ($offset + $select)) ? $allRow : ($offset + $select));

        return [
            'row'    => $row,
            'allRow' => $allRow,
            'list'   => $list,
        ];
    }

    private function applySearch($query, $request)
    {
        return $query->where(function ($query) use ($request) {

            if (! empty($request->name)) {
                $searchTerm = '%'.$request->name.'%';

                $query->whereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.nombre))), ' ', '')
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                )->orWhereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_paterno))), ' ', '')
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                )->orWhereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_materno))), ' ', '')
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                )->orWhereRaw(
                    "REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.nombre))), ' ', '') ||
                     REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_paterno))), ' ', '') ||
                     REPLACE(UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.apellido_materno))), ' ', '')
                     LIKE REPLACE(UPPER(TRIM(public.unaccent(?))), ' ', '')",
                    [$searchTerm]
                );
            }

            if (! empty($request->curp)) {
                $query->whereRaw(
                    'UPPER(TRIM(public.unaccent(public.a2_acciones_empleados.curp)))
                     LIKE UPPER(TRIM(public.unaccent(?)))',
                    ['%'.$request->curp.'%']
                );
            }

            if (! empty($request->id_accion)) {
                $query->where('public.a2_acciones_empleados.id_accion', '=', $request->id_accion);
            }
        });
    }

    private function isAdminOrRevisorEst($user): bool
    {
        if (! $user) {
            return false;
        }

        // Spatie
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin_oc', 'admin', 'revisor_est'])) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            if (
                $user->hasRole('admin_oc') ||
                $user->hasRole('admin') ||
                $user->hasRole('revisor_est')
            ) {
                return true;
            }
        }

        // método auxiliar existente
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        // rol_id clásico
        if (isset($user->rol_id) && (int) $user->rol_id === 1) {
            return true;
        }

        // booleano
        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        // nombres textuales posibles
        $roleCandidates = [
            $user->role ?? null,
            $user->rol ?? null,
            $user->rol_nombre ?? null,
            $user->nombre_rol ?? null,
            $user->perfil ?? null,
        ];

        foreach ($roleCandidates as $role) {
            $role = strtolower(trim((string) $role));
            if (in_array($role, ['admin_oc', 'admin', 'revisor_est'], true)) {
                return true;
            }
        }

        return false;
    }
}