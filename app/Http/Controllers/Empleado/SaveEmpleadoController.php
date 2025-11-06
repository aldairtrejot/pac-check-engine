<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaveEmpleadoController extends Controller
{
    public function save(Request $request)
    {
        // 1) Validación
        $validated = $request->validate([
            'curp_base'         => 'nullable|string|max:18',
            'curp'              => 'required|string|max:18|regex:/^[A-Z0-9]+$/',
            'rfc'               => 'nullable|string|max:13|regex:/^[A-Z0-9]+$/',
            'sexo'              => 'required|string|in:HOMBRE,MUJER',
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
            'quincena'          => 'nullable|integer|min:1|max:24',
        ], [
            'curp.required' => 'El campo CURP es obligatorio.',
            'curp.regex'    => 'El CURP solo puede contener letras mayúsculas y números.',
            'sexo.required' => 'El campo Sexo es obligatorio.',
            'sexo.in'       => 'El sexo debe ser HOMBRE o MUJER.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'apellido_paterno.required' => 'El campo Apellido Paterno es obligatorio.',
            'nombre_puesto.required' => 'El campo Nombre del Puesto es obligatorio.',
        ]);

        try {
            DB::beginTransaction();

            $curpNuevo = strtoupper(trim($validated['curp']));
            $curpBase  = !empty($validated['curp_base'])
                ? strtoupper(trim($validated['curp_base']))
                : null;

            // 2) Checar duplicado en plantilla
            $existeNuevo = DB::table('public.a2_acciones_capacitacion')
                ->whereRaw('UPPER(TRIM(curp)) = ?', [$curpNuevo])
                ->exists();

            if ($existeNuevo) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->withErrors(['curp' => 'Ya existe un empleado con esta CURP en la plantilla.']);
            }

            // 3) Tomar registro base (si se capturó CURP base)
            $datosBase = null;
            if ($curpBase) {
                $datosBase = DB::table('public.a2_acciones_capacitacion')
                    ->whereRaw('UPPER(TRIM(curp)) = ?', [$curpBase])
                    ->first();
            }

            // 4) Siguiente id_cat (campo texto) y id_puesto
            $maxIdCat  = DB::table('public.a2_acciones_capacitacion')->max('id_cat');
            $nextIdCat = ((int)($maxIdCat ?? 9999)) + 1;

            $maxIdPuesto  = DB::table('public.a2_acciones_capacitacion')->max('id_puesto');
            $nextIdPuesto = ($maxIdPuesto ?? 0) + 1;

            // 5) Datos para plantilla (a2_acciones_capacitacion)
            $insertCap = [
                'id_cat'            => (string)$nextIdCat,
                'ramo'              => $datosBase->ramo ?? null,
                'ur'                => $datosBase->ur ?? null,
                'id_puesto'         => $nextIdPuesto,
                'curp'              => $curpNuevo,
                'sexo'              => strtoupper($validated['sexo']),
                'nombre_puesto'     => strtoupper(trim($validated['nombre_puesto'])),
                'puesto'            => strtoupper(trim($validated['nombre_puesto'])),
                'nivel_salarial'    => !empty($validated['nivel_salarial'])
                                        ? strtoupper(trim($validated['nivel_salarial']))
                                        : ($datosBase->nivel_salarial ?? null),
                'tipo_personal'     => $datosBase->tipo_personal ?? null,
                'quincena'          => $validated['quincena'] ?? ($datosBase->quincena ?? 18),
                'rfc'               => !empty($validated['rfc'])
                                        ? strtoupper(trim($validated['rfc']))
                                        : ($datosBase->rfc ?? null),
                'codigo_puesto'     => !empty($validated['codigo_puesto'])
                                        ? strtoupper(trim($validated['codigo_puesto']))
                                        : ($datosBase->codigo_puesto ?? null),
                'clave_clues'       => !empty($validated['clave_clues'])
                                        ? strtoupper(trim($validated['clave_clues']))
                                        : ($datosBase->clave_clues ?? null),
                'descripcion_clues' => !empty($validated['descripcion_clues'])
                                        ? strtoupper(trim($validated['descripcion_clues']))
                                        : ($datosBase->descripcion_clues ?? null),
                'tipo_contratacion' => !empty($validated['tipo_contratacion'])
                                        ? $validated['tipo_contratacion']
                                        : ($datosBase->tipo_contratacion ?? null),
                'nomina'            => !empty($validated['nomina'])
                                        ? $validated['nomina']
                                        : ($datosBase->nomina ?? null),
                'nombre'            => strtoupper(trim($validated['nombre'])),
                'apellido_paterno'  => strtoupper(trim($validated['apellido_paterno'])),
                'apellido_materno'  => !empty($validated['apellido_materno'])
                                        ? strtoupper(trim($validated['apellido_materno']))
                                        : null,
                'nivel_atencion'    => !empty($validated['nivel_atencion'])
                                        ? $validated['nivel_atencion']
                                        : ($datosBase->nivel_atencion ?? null),
                'entidad'           => !empty($validated['entidad'])
                                        ? $validated['entidad']
                                        : ($datosBase->entidad ?? null),
                'num_cursos'        => 0,
                'activo'            => 1,
            ];

            // Copiar campos de acciones/finalidades si hay base (opcional)
            if ($datosBase) {
                $camposACopiar = [
                    'id_accion_1','id_finalidad_1','id_finalidad_1_bis','col_l',
                    'id_accion_2','id_finalidad_2','id_finalidad_2_bis','col_p',
                    'id_accion_3','id_finalidad_3','id_finalidad_3_bis','col_t',
                    'id_accion_4','id_finalidad_4','id_finalidad_4_bis','col_x',
                    'id_accion_5','id_finalidad_5','id_finalidad_5_bis','col_ab',
                    'id_accion_6','id_finalidad_6','id_finalidad_6_bis','col_af',
                    'id_accion_7','id_finalidad_7','id_finalidad_7_bis','col_aj',
                    'id_accion_8','id_finalidad_8','id_finalidad_8_bis','col_an',
                    'id_accion_9','id_finalidad_9','id_finalidad_9_bis','col_ar',
                    'id_accion_10','id_finalidad_10','id_finalidad_10_bis','col_av',
                    'id_accion_11','id_finalidad_11','id_finalidad_11_bis','col_az',
                    'id_accion_12','id_finalidad_12','id_finalidad_12_bis','col_bd',
                    'id_accion_13','id_finalidad_13','id_finalidad_13_bis','col_bh',
                    'id_accion_14','id_finalidad_14','id_finalidad_14_bis','col_bl',
                    'id_accion_15','id_finalidad_15','id_finalidad_15_bis','col_bp',
                    'id_accion_16','id_finalidad_16','id_finalidad_16_bis','col_bt',
                    'id_accion_17','id_finalidad_17','id_finalidad_17_bis','col_bx',
                    'id_accion_18','id_finalidad_18','id_finalidad_18_bis','col_cb',
                    'id_accion_19','id_finalidad_19','id_finalidad_19_bis','col_cf',
                    'id_accion_20','id_finalidad_20','id_finalidad_20_bis','col_cj',
                    'col_ck','codigo_claves_de_acciones_de_capacitacion','contador',
                ];

                foreach ($camposACopiar as $campo) {
                    if (property_exists($datosBase, $campo)) {
                        $insertCap[$campo] = $datosBase->$campo;
                    }
                }
            }

            // Insertar en plantilla
            DB::table('public.a2_acciones_capacitacion')->insert($insertCap);

            // 6) Insertar cursos base en a2_acciones_empleados
            //    (equivalente a tus 2 INSERT de ejemplo)
            $configCursos = [
                ['id_accion' => 1, 'id_finalidad' => 3],
                ['id_accion' => 2, 'id_finalidad' => 6],
            ];

            $maxIdEmpl = DB::table('public.a2_acciones_empleados')
                ->max('id_empl_accion') ?? 0;

            foreach ($configCursos as $cfg) {
                // Datos de la acción
                $accion = DB::table('public.a1_cat_acciones')
                    ->select('duracion_hrs', 'tematica')
                    ->where('id_accion', $cfg['id_accion'])
                    ->first();

                if (! $accion) {
                    continue; // si no existe la acción, la saltamos
                }

                // id_tematica según tematica de la acción
                $idTematica = DB::table('public.cat_tematica')
                    ->whereRaw('TRIM(UPPER(tematica)) = TRIM(UPPER(?))', [$accion->tematica])
                    ->value('id_tematica');

                // Consecutivo de curso para este empleado
                $maxNumCurso = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('UPPER(TRIM(curp)) = ?', [$curpNuevo])
                    ->max('id_num_curso');

                $nextNumCurso = $maxNumCurso ? $maxNumCurso + 1 : 1;

                // Nuevo id_empl_accion
                $maxIdEmpl++;

                DB::table('public.a2_acciones_empleados')->insert([
                    'id_empl_accion'   => $maxIdEmpl,
                    'id_puesto'        => $nextIdPuesto,
                    'curp'             => $curpNuevo,
                    'id_accion'        => $cfg['id_accion'],
                    'id_finalidad'     => $cfg['id_finalidad'],
                    'horas_real'       => null,
                    'id_instancia'     => null,
                    'costo_unitario'   => null,
                    'fecha_ini'        => null,
                    'fecha_fin'        => null,
                    'id_trimestre'     => null,
                    'id_num_curso'     => $nextNumCurso,
                    'eval_aprendizaje' => null,
                    'observaciones'    => null,
                    'id_cat_estatus'   => null,
                    'id_cat_tematica'  => $idTematica,
                    'horas_progamadas' => $accion->duracion_hrs,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('empleado')
                ->with('success', 'Empleado y cursos base agregados correctamente. CURP: '.$curpNuevo);

        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Error al guardar empleado', [
                'error' => $th->getMessage(),
                'file'  => $th->getFile(),
                'line'  => $th->getLine(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'general' => 'Ocurrió un error al guardar el empleado. Revisa el log si persiste.',
                ]);
        }
    }
}
