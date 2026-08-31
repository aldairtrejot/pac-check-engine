<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Support\EmpleadoCatalogs;
use Illuminate\Http\Request;
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

    public function view(Request $request)
    {
        $user = Auth::user();
        $puestos = collect();
        $puestoOptions = collect();

        try {
            $puestos = EmpleadoCatalogs::puestos();
            $puestoOptions = $puestos->map(fn ($puesto) => [
                'label' => $puesto->label,
                'codigo' => $puesto->codigo_puesto,
                'puesto' => $puesto->puesto,
                'nivel' => $puesto->nivel,
            ])->values();
        } catch (\Throwable $th) {
            Log::error('Error al cargar catálogos del formulario de empleado', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
        }

        $codigoPuestoOld = (string) $request->old('codigo_puesto', '');
        $puestoOld = $codigoPuestoOld !== ''
            ? $puestos->firstWhere('codigo_puesto', $codigoPuestoOld)
            : null;

        $catalogProps = [
            'puestos' => $puestoOptions,
            'cluesSearchUrl' => route('empleado.catalogos.clues'),
            'old' => [
                'codigo_puesto' => $codigoPuestoOld,
                'puesto_label' => (string) $request->old('puesto_label', $puestoOld->label ?? ''),
                'nombre_puesto' => (string) $request->old('nombre_puesto', $puestoOld->puesto ?? ''),
                'nivel_salarial' => (string) $request->old('nivel_salarial', $puestoOld->nivel ?? ''),
                'clues_catalog_key' => (string) $request->old('clues_catalog_key', ''),
                'clues_label' => (string) $request->old('clues_label', ''),
                'id_clues' => (string) $request->old('id_clues', ''),
                'clave_clues' => (string) $request->old('clave_clues', ''),
                'descripcion_clues' => (string) $request->old('descripcion_clues', ''),
                'nomina' => (string) $request->old('nomina', ''),
                'entidad' => (string) $request->old('entidad', ''),
            ],
        ];

        return view('empleado.empleado', [
            'usuario' => $user,
            'puestos' => $puestos,
            'puestoOptions' => $puestoOptions,
            'catalogProps' => $catalogProps,
        ]);
    }
}
