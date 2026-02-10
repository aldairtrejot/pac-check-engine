<?php

namespace App\Http\Middleware;

use App\Support\PacVisibility;
use Closure;
use Illuminate\Http\Request;

class RequireStateForOperatives
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) abort(403);

        if ($user->isOperative()) {
            if (empty($user->id_entidad) || empty($user->id_tipo_nomina)) {
                abort(403, 'Usuario operativo sin entidad/tipo de nómina asignados.');
            }

            // Si su nómina es HRAES => debe tener id_clues
            if (PacVisibility::isHraesTipoNomina((int) $user->id_tipo_nomina) && empty($user->id_clues)) {
                abort(403, 'Usuario operativo HRAES sin CLUES asignado.');
            }
        }

        return $next($request);
    }
}