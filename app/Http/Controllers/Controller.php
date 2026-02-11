<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * ✅ Antes validaba por correo.
     * ✅ Ahora valida por roles (desde BD).
     */
    protected function ensurePacAdmin(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'No autenticado');
        }

        // Ajusta roles permitidos para módulos PAC:
        if (! $user->hasAnyRole(['admin_oc', 'supervisor_oc'])) {
            abort(403, 'No tienes permiso para acceder a este módulo.');
        }
    }
}