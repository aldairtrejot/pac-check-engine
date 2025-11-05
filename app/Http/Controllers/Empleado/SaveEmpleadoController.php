<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaveEmpleadoController extends Controller
{
    public function save(Request $request)
    {
        // 1) Validación (OJO: SIN "exists")
        $validated = $request->validate([
            'curp_base'         => 'required|string|max:18',   // CURP del empleado plantilla
            'curp'              => 'required|string|max:18',   // CURP del nuevo empleado
            'sexo'              => 'required|string|max:10',
            'nombre'            => 'required|string|max:100',
            'apellido_paterno'  => 'required|string|max:100',
            'apellido_materno'  => 'nullable|string|max:100',
            'nombre_puesto'     => 'required|string|max:200',
            'nivel_salarial'    => 'nullable|string|max:50',
            'tipo_personal'     => 'nullable|string|max:50',
            'tipo_contratacion' => 'nullable|string|max:50',
            'nomina'            => 'nullable|string|max:50',
            'nivel_atencion'    => 'nullable|string|max:50',
            'entidad'           => 'nullable|string|max:100',
            'ramo'              => 'nullable|integer',
            'ur'                => 'nullable|string|max:50',
            'quincena'          => 'nullable|string|max:10',
        ]);

        try {
            DB::beginTransaction();

            // 2) Buscar empleado base en public.a2_acciones_capacitacion
            $base = DB::table('public.a2_acciones_capacitacion')
                ->where('curp', $validated['curp_base'])
                ->first();

            if (!$base) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->withErrors([
                        'curp_base' => 'La CURP base no existe en la plantilla (a2_acciones_capacitacion).',
                    ]);
            }

            // 3) Evitar duplicar CURP nuevo
            $existeNuevo = DB::table('public.a2_acciones_capacitacion')
                ->where('curp', $validated['curp'])
                ->exists();

            if ($existeNuevo) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->withErrors([
                        'curp' => 'Ya existe un registro con esa CURP en la plantilla.',
                    ]);
            }

            // 4) Calcular nuevos id_cat e id_puesto
            $nextIdCat = (DB::table('public.a2_acciones_capacitacion')->max('id_cat') ?? 0) + 1;
            $nextIdPuesto = (DB::table('public.a2_acciones_capacitacion')->max('id_puesto') ?? 0) + 1;

            // 5) Clonar los datos del base
            $data = (array) $base;

            // Sobrescribimos llaves y datos de persona
            $data['id_cat']          = $nextIdCat;
            $data['id_puesto']       = $nextIdPuesto;
            $data['curp']            = strtoupper($validated['curp']);
            $data['sexo']            = strtoupper($validated['sexo']);
            $data['nombre']          = strtoupper($validated['nombre']);
            $data['apellido_paterno']= strtoupper($validated['apellido_paterno']);
            $data['apellido_materno']= strtoupper($validated['apellido_materno'] ?? '');
            $data['nombre_puesto']   = strtoupper($validated['nombre_puesto']);
            $data['nivel_salarial']  = $validated['nivel_salarial'] ?? $base->nivel_salarial;
            $data['tipo_personal']   = $validated['tipo_personal'] ?? $base->tipo_personal;
            $data['tipo_contratacion'] = $validated['tipo_contratacion'] ?? $base->tipo_contratacion;
            $data['nomina']          = $validated['nomina'] ?? $base->nomina;
            $data['nivel_atencion']  = $validated['nivel_atencion'] ?? $base->nivel_atencion;
            $data['entidad']         = $validated['entidad'] ?? $base->entidad;
            $data['ramo']            = $validated['ramo'] ?? $base->ramo;
            $data['ur']              = $validated['ur'] ?? $base->ur;
            $data['quincena']        = $validated['quincena'] ?? ($base->quincena ?? '18'); // default 18 si no viene nada

            // 6) Insertar nuevo registro en plantilla
            DB::table('public.a2_acciones_capacitacion')->insert($data);

            DB::commit();

            return redirect()
                ->route('pac')
                ->with('success', 'Empleado agregado correctamente a la plantilla. Ahora puedes asignarle cursos desde PAC.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Ocurrió un error al guardar el empleado. Revisa el log si persiste.',
                ]);
        }
    }
}
