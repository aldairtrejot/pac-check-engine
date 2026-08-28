<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Support\EmpleadoCatalogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $puestos = collect();

        try {
            $puestos = EmpleadoCatalogs::puestos();
        } catch (\Throwable $th) {
            Log::error('Error al cargar catálogos del formulario de empleado', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
        }

        return view('empleado.empleado', [
            'usuario' => $user,
            'puestos' => $puestos,
        ]);
    }
}
