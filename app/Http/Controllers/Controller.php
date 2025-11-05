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
     * Solo permite acceso a ciertos correos para módulos PAC
     * (acciones, temáticas, instancias, etc.).
     */
    protected function ensurePacAdmin(): void
    {
        $allowed = [
            'soporte_rh@imssbienestar.gob.mx',
            'yessica.colorado@imssbienestar.gob.mx',
            'reforzamientorh012@imssbienestar.gob.mx',
        ];

        $user = Auth::user();

        if (! $user || ! in_array($user->email, $allowed, true)) {
            abort(403, 'No tienes permiso para acceder a este módulo.');
        }
    }
}
