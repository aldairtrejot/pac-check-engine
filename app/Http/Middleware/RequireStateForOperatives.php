<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireStateForOperatives
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) abort(403);

        if ($user->isOperative() && empty($user->id_cat_entidad)) {
            abort(403, 'Usuario operativo sin estado asignado.');
        }

        return $next($request);
    }
}
