<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ViewEmpleadoController extends Controller
{
    /**
     * Correos con permiso para usar esta sección.
     */
    protected $allowedEmails = [
        'soporte_rh@imssbienestar.gob.mx',
        'yessica.colorado@imssbienestar.gob.mx',
        'reforzamientorh012@imssbienestar.gob.mx',
    ];

    public function __construct()
    {
        // Restringimos acceso solo a los correos autorizados
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            if (! $user || ! in_array($user->email, $this->allowedEmails)) {
                abort(403, 'No tienes permiso para acceder a esta sección.');
            }

            return $next($request);
        });
    }

    /**
     * Muestra el formulario para agregar un empleado
     * usando una plantilla base de a2_acciones_capacitacion.
     */
    public function view()
    {
        return view('empleado.empleado');
    }
}
