<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Si no se pasan roles, deja pasar
        if (empty($roles)) {
            return $next($request);
        }

        // Usa tu método del modelo: hasAnyRole()
        if (!method_exists($user, 'hasAnyRole') || !$user->hasAnyRole($roles)) {
            abort(403, 'No autorizado');
        }

        return $next($request);
    }
}
