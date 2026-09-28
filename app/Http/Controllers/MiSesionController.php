<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MiSesionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Datos esenciales del usuario en sesión
        |--------------------------------------------------------------------------
        | Solo se consulta el usuario autenticado. No se recibe ID por URL para evitar
        | que alguien intente consultar información de otro usuario.
        */
        $usuarioQuery = DB::table('administracion.users as u')
            ->leftJoin('administracion.cat_entidad as ce', 'ce.id_entidad', '=', 'u.id_entidad')
            ->leftJoin('administracion.cat_tipo_nomina as ctn', 'ctn.id_tipo_nomina', '=', 'u.id_tipo_nomina')
            ->leftJoin('administracion.cat_clues as cc', 'cc.id_clues', '=', 'u.id_clues');

        if ($this->tableExists('public', 'cat_clues_bi')) {
            $usuarioQuery->leftJoin('public.cat_clues_bi as cbi_id', function ($join) {
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

        $usuarioQuery->where('u.id', $user->id)
            ->select([
                'u.id',
                'u.name',
                'u.email',
                'u.status',
                'u.id_entidad',
                'u.id_tipo_nomina',
                'u.id_clues',

                DB::raw("COALESCE(ce.nombre, 'No asignado') as entidad_nombre"),
                DB::raw("COALESCE(ctn.codigo, 'No asignado') as tipo_nomina_codigo"),
            ]);

        if ($this->tableExists('public', 'cat_clues_bi')) {
            $usuarioQuery->addSelect(DB::raw("
                COALESCE(
                    NULLIF(BTRIM(cc.clues), ''),
                    NULLIF(BTRIM(cbi_id.clave_clues), ''),
                    'No asignado'
                ) as clues_codigo
            "));
        } else {
            $usuarioQuery->addSelect(DB::raw("COALESCE(cc.clues, 'No asignado') as clues_codigo"));
        }

        $usuario = $usuarioQuery->first();

        if (! $usuario) {
            abort(404, 'Usuario no encontrado.');
        }

        /*
        |--------------------------------------------------------------------------
        | Roles del usuario
        |--------------------------------------------------------------------------
        | Se consultan desde administracion.user_roles y administracion.roles.
        | Si el sistema no encuentra roles, se muestra "Sin rol asignado".
        */
        $roles = DB::table('administracion.user_roles as ur')
            ->join('administracion.roles as r', 'r.id', '=', 'ur.role_id')
            ->where('ur.user_id', $user->id)
            ->where('r.is_active', true)
            ->orderBy('r.name')
            ->pluck('r.name')
            ->filter(fn ($rol) => trim((string) $rol) !== '')
            ->values()
            ->all();

        return view('mi-sesion.index', [
            'usuario' => $usuario,
            'roles'   => $roles,
        ]);
    }

    private function tableExists(string $schema, string $table): bool
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
}
