<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! method_exists($user, 'hasAnyRole') || ! $user->hasAnyRole($roles)) {
            abort(403, 'No autorizado');
        }

        return $next($request);
    }
}