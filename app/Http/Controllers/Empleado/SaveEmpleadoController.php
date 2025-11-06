<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaveEmpleadoController extends Controller
{
    public function save(Request $request)
    {
        // 1) Validación básica
        $validated = $request->validate([
            'curp_base'         => 'nullable|string|max:18',   // Solo informativo por ahora
            'curp'              => 'required|string|max:18',   // CURP del nuevo empleado
            'rfc'               => 'nullable|string|max:13',
            'sexo'              => 'required|string|max:10',
            'nombre'            => 'required|string|max:100',
            'apellido_paterno'  => 'required|string|max:100',
            'apellido_materno'  => 'nullable|string|max:100',
            'nombre_puesto'     => 'required|string|max:200',
            'codigo_puesto'     => 'nullable|string|max:50',
            'nivel_salarial'    => 'nullable|string|max:50',
            'tipo_contratacion' => 'nullable|string|max:50',
            'nomina'            => 'nullable|string|max:50',
            'nivel_atencion'    => 'nullable|string|max:50',
            'entidad'           => 'nullable|string|max:100',
            'clave_clues'       => 'nullable|string|max:50',
            'descripcion_clues' => 'nullable|string|max:255',
            'quincena'          => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $curpNuevo = strtoupper(trim($validated['curp']));

            // 2) Evitar duplicar CURP nuevo
            $existeNuevo = DB::table('public.a2_acciones_capacitacion')
                ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curpNuevo])
                ->exists();

            if ($existeNuevo) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->withErrors([
                        'curp' => 'Ya existe un registro con esa CURP en la plantilla (a2_acciones_capacitacion).',
                    ]);
            }

            // 3) Nuevo id_puesto (PK, NOT NULL)
            $maxIdPuesto  = DB::table('public.a2_acciones_capacitacion')->max('id_puesto');
            $nextIdPuesto = ($maxIdPuesto ?? 0) + 1;

            // 4) Armar datos mínimos a insertar
            $insert = [
                'id_puesto'         => $nextIdPuesto,
                'id_cat'            => null, // si luego quieres, aquí puedes meter algo fijo
                'ramo'              => null,
                'ur'                => null,
                'curp'              => $curpNuevo,
                'sexo'              => strtoupper($validated['sexo']),
                'nombre_puesto'     => strtoupper($validated['nombre_puesto']),
                'puesto'            => strtoupper($validated['nombre_puesto']),
                'nivel_salarial'    => $validated['nivel_salarial'] ?? null,
                'tipo_personal'     => null,
                'quincena'          => $validated['quincena'] ?? 18,
                'rfc'               => strtoupper($validated['rfc'] ?? ''),
                'codigo_puesto'     => strtoupper($validated['codigo_puesto'] ?? ''),
                'clave_clues'       => strtoupper($validated['clave_clues'] ?? ''),
                'descripcion_clues' => strtoupper($validated['descripcion_clues'] ?? ''),
                'tipo_contratacion' => $validated['tipo_contratacion'] ?? null,
                'nomina'            => $validated['nomina'] ?? null,
                'nombre'            => strtoupper($validated['nombre']),
                'apellido_paterno'  => strtoupper($validated['apellido_paterno']),
                'apellido_materno'  => strtoupper($validated['apellido_materno'] ?? ''),
                'nivel_atencion'    => $validated['nivel_atencion'] ?? null,
                'entidad'           => $validated['entidad'] ?? null,
                'num_cursos'        => 0,
                'activo'            => 1,
            ];

            // 5) Insertar nuevo registro
            DB::table('public.a2_acciones_capacitacion')->insert($insert);

            DB::commit();

            // Te regreso a la misma pantalla de "Agregar empleado"
            return redirect()
                ->route('empleado')
                ->with('success', 'Empleado agregado correctamente a la plantilla. Ahora puedes asignarle cursos desde PAC.');

        } catch (\Throwable $th) {
            DB::rollBack();

            \Log::error('Error al guardar empleado desde formulario PAC', [
                'error' => $th->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Ocurrió un error al guardar el empleado: '.$th->getMessage(),
                ]);
        }
    }
}
