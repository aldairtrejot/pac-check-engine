<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ViewEmpleadoController extends Controller
{
    /**
     * Correos con permiso para usar esta sección.
     * Solo estos usuarios pueden agregar empleados al sistema.
     */
    protected $allowedEmails = [
        'soporte_rh@imssbienestar.gob.mx',
        'yessica.colorado@imssbienestar.gob.mx',
        'reforzamientorh012@imssbienestar.gob.mx',
    ];

    public function __construct()
    {
        // IMPORTANTE: El middleware de autenticación debe ir primero
        $this->middleware('auth');
        
        // Luego verificamos los permisos específicos
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            // Verificar que el usuario esté autenticado
            if (!$user) {
                return redirect()->route('login')
                    ->withErrors(['error' => 'Debes iniciar sesión para acceder a esta sección.']);
            }

            // Verificar que el correo del usuario esté en la lista de permitidos
            if (!in_array($user->email, $this->allowedEmails)) {
                abort(403, 'Acceso denegado. Solo personal autorizado de Recursos Humanos puede agregar empleados al sistema.');
            }

            return $next($request);
        });
    }

    /**
     * Muestra el formulario para agregar un nuevo empleado.
     * 
     * @return \Illuminate\View\View
     */
    public function view()
    {
        $user = Auth::user();
        
        return view('empleado.empleado', [
            'usuario' => $user,
        ]);
    }
}