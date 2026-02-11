<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ViewEmpleadoController extends Controller
{
    public function __construct()
    {
        // Autenticación primero
        $this->middleware('auth');

        // ✅ Permisos por rol (NO por correo)
        // Ajusta los roles que deben poder agregar empleados:
        $this->middleware('role:admin_oc,supervisor_oc');
    }

    public function view()
    {
        $user = Auth::user();

        return view('empleado.empleado', [
            'usuario' => $user,
        ]);
    }
}