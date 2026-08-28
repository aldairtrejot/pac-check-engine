<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Support\EmpleadoCatalogs;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SaveEmpleadoController extends Controller
{
    private const OBSERVACION_CURSO_OBLIGATORIO = 'OBLIGATORIO';

    public function save(Request $request)
    {
        $request->merge([
            'curp' => EmpleadoCatalogs::norm($request->input('curp')),
            'rfc' => $request->filled('rfc') ? EmpleadoCatalogs::norm($request->input('rfc')) : null,
            'sexo' => $request->filled('sexo') ? EmpleadoCatalogs::norm($request->input('sexo')) : null,
            'codigo_puesto' => EmpleadoCatalogs::norm($request->input('codigo_puesto')),
            'clave_clues' => EmpleadoCatalogs::norm($request->input('clave_clues')),
        ]);

        // 1) Validación
        $validated = $request->validate([
            'curp_base'         => 'nullable|string|max:18',
            'curp'              => 'required|string|size:18|regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/',
            'rfc'               => 'nullable|string|max:13|regex:/^[A-Z0-9]+$/',
            'sexo'              => 'nullable|string|in:HOMBRE,MUJER',
            'nombre'            => 'required|string|max:100',
            'apellido_paterno'  => 'required|string|max:100',
            'apellido_materno'  => 'nullable|string|max:100',
            'nombre_puesto'     => 'nullable|string|max:200',
            'codigo_puesto'     => 'required|string|max:50',
            'nivel_salarial'    => 'nullable|string|max:50',
            'tipo_contratacion' => 'nullable|string|max:50',
            'nomina'            => 'nullable|string|max:50',
            'nivel_atencion'    => 'nullable|string|max:50',
            'entidad'           => 'nullable|string|max:100',
            'clues_catalog_key'  => 'required|string|max:2000',
            'id_clues'          => 'nullable|integer',
            'clave_clues'       => 'required|string|max:50',
            'descripcion_clues' => 'nullable|string|max:255',
            'quincena'          => 'nullable|integer|min:1|max:24',
            'observaciones'     => 'required|string|max:1000',
        ], [
            'curp.required' => 'El campo CURP es obligatorio.',
            'curp.size'     => 'El CURP debe tener exactamente 18 caracteres.',
            'curp.regex'    => 'El CURP no tiene un formato válido.',
            'sexo.in'       => 'El sexo debe ser HOMBRE o MUJER.',
            'nombre.required' => 'El campo Nombre es obligatorio.',
            'apellido_paterno.required' => 'El campo Apellido Paterno es obligatorio.',
            'codigo_puesto.required' => 'Selecciona un puesto del catálogo.',
            'clues_catalog_key.required' => 'Selecciona una CLUES del catálogo.',
            'clave_clues.required' => 'Selecciona una CLUES del catálogo.',
            'observaciones.required' => 'El campo Observaciones es obligatorio.',
        ]);

        try {
            $curpNuevo = $validated['curp'];
            $sexoCurp = $this->sexoFromCurp($curpNuevo);

            if ($sexoCurp === null) {
                throw ValidationException::withMessages([
                    'curp' => 'No se pudo identificar el sexo desde la CURP capturada.',
                ]);
            }

            if (! empty($validated['sexo']) && $validated['sexo'] !== $sexoCurp) {
                throw ValidationException::withMessages([
                    'sexo' => 'El sexo no coincide con el carácter 11 de la CURP.',
                ]);
            }

            $puestoCatalogo = EmpleadoCatalogs::findPuestoByCodigo($validated['codigo_puesto']);

            if (! $puestoCatalogo) {
                throw ValidationException::withMessages([
                    'codigo_puesto' => 'El puesto seleccionado no existe en el catálogo.',
                ]);
            }

            $cluesCatalogo = EmpleadoCatalogs::findCluesByCatalogKey($validated['clues_catalog_key']);

            if (! $cluesCatalogo) {
                throw ValidationException::withMessages([
                    'clave_clues' => 'La CLUES seleccionada no existe en el catálogo.',
                ]);
            }

            DB::beginTransaction();

            // CURP base por defecto, aunque no venga en el formulario.
            // Se usa siempre OIJN850210MMCRMN07 como plantilla.
            $curpBase = EmpleadoCatalogs::norm($validated['curp_base'] ?? 'OIJN850210MMCRMN07');

            /*
            |--------------------------------------------------------------------------
            | Bloqueo de alta de empleado
            |--------------------------------------------------------------------------
            | Este flujo calcula ids con MAX + 1 por compatibilidad con tablas
            | existentes. El advisory lock evita duplicados si dos usuarios dan de
            | alta empleados al mismo tiempo dentro de PostgreSQL.
            */
            DB::statement('SELECT pg_advisory_xact_lock(2026071401)');

            // 2) Checar duplicado en plantilla
            $existeNuevo = DB::table('public.a2_acciones_capacitacion')
                ->whereRaw('UPPER(TRIM(curp)) = ?', [$curpNuevo])
                ->exists();

            if ($existeNuevo) {
                DB::rollBack();

                return back()
                    ->withInput()
                    ->withErrors([
                        'curp' => 'Ya existe un empleado con esta CURP en la plantilla.',
                    ]);
            }

            // 3) Tomar registro base usando la CURP fija OIJN850210MMCRMN07
            $datosBase = DB::table('public.a2_acciones_capacitacion')
                ->whereRaw('UPPER(TRIM(curp)) = ?', [$curpBase])
                ->first();

            // 4) Siguiente id_cat y id_puesto
            $maxIdCat  = DB::table('public.a2_acciones_capacitacion')->max('id_cat');
            $nextIdCat = ((int) ($maxIdCat ?? 9999)) + 1;

            $maxIdPuesto  = DB::table('public.a2_acciones_capacitacion')->max('id_puesto');
            $nextIdPuesto = ($maxIdPuesto ?? 0) + 1;

            $nivelSalarial = $puestoCatalogo->nivel !== ''
                ? $puestoCatalogo->nivel
                : (!empty($validated['nivel_salarial'])
                    ? EmpleadoCatalogs::norm($validated['nivel_salarial'])
                    : ($datosBase->nivel_salarial ?? null));

            $nomina = $cluesCatalogo->nomina !== ''
                ? $cluesCatalogo->nomina
                : (!empty($validated['nomina'])
                    ? EmpleadoCatalogs::norm($validated['nomina'])
                    : ($datosBase->nomina ?? null));

            $entidad = $cluesCatalogo->entidad !== ''
                ? $cluesCatalogo->entidad
                : (!empty($validated['entidad'])
                    ? EmpleadoCatalogs::norm($validated['entidad'])
                    : ($datosBase->entidad ?? null));

            // 5) Datos para plantilla: public.a2_acciones_capacitacion
            $insertCap = [
                'id_cat'            => (int) $nextIdCat,
                'ramo'              => $datosBase->ramo ?? null,
                'ur'                => $datosBase->ur ?? null,
                'id_puesto'         => $nextIdPuesto,
                'curp'              => $curpNuevo,
                'sexo'              => $sexoCurp,
                'nombre_puesto'     => $puestoCatalogo->puesto,
                'puesto'            => $puestoCatalogo->puesto,

                'nivel_salarial'    => $nivelSalarial,

                'tipo_personal'     => $datosBase->tipo_personal ?? null,

                'quincena'          => $validated['quincena'] ?? ($datosBase->quincena ?? 18),

                'rfc'               => !empty($validated['rfc'])
                                        ? strtoupper(trim($validated['rfc']))
                                        : ($datosBase->rfc ?? null),

                'codigo_puesto'     => $puestoCatalogo->codigo_puesto,

                'clave_clues'       => $cluesCatalogo->clave_clues,

                'descripcion_clues' => $cluesCatalogo->descripcion_clues,

                'tipo_contratacion' => !empty($validated['tipo_contratacion'])
                                        ? $validated['tipo_contratacion']
                                        : ($datosBase->tipo_contratacion ?? null),

                'nomina'            => $nomina,

                'nombre'            => strtoupper(trim($validated['nombre'])),

                'apellido_paterno'  => strtoupper(trim($validated['apellido_paterno'])),

                'apellido_materno'  => !empty($validated['apellido_materno'])
                                        ? strtoupper(trim($validated['apellido_materno']))
                                        : null,

                'nivel_atencion'    => !empty($validated['nivel_atencion'])
                                        ? $validated['nivel_atencion']
                                        : ($datosBase->nivel_atencion ?? null),

                'entidad'           => $entidad,

                // Nuevos valores por defecto solicitados
                'num_cursos'        => 1210,
                'activo'            => 2,
                'id_unidad'         => 12,
                'id_coordinacion'   => 10,
            ];

            // Copiar campos de acciones/finalidades si hay base
            if ($datosBase) {
                $camposACopiar = [
                    'id_accion_1',
                    'id_finalidad_1',
                    'id_finalidad_1_bis',
                    'col_l',

                    'id_accion_2',
                    'id_finalidad_2',
                    'id_finalidad_2_bis',
                    'col_p',

                    'id_accion_3',
                    'id_finalidad_3',
                    'id_finalidad_3_bis',
                    'col_t',

                    'id_accion_4',
                    'id_finalidad_4',
                    'id_finalidad_4_bis',
                    'col_x',

                    'id_accion_5',
                    'id_finalidad_5',
                    'id_finalidad_5_bis',
                    'col_ab',

                    'id_accion_6',
                    'id_finalidad_6',
                    'id_finalidad_6_bis',
                    'col_af',

                    'id_accion_7',
                    'id_finalidad_7',
                    'id_finalidad_7_bis',
                    'col_aj',

                    'id_accion_8',
                    'id_finalidad_8',
                    'id_finalidad_8_bis',
                    'col_an',

                    'id_accion_9',
                    'id_finalidad_9',
                    'id_finalidad_9_bis',
                    'col_ar',

                    'id_accion_10',
                    'id_finalidad_10',
                    'id_finalidad_10_bis',
                    'col_av',

                    'id_accion_11',
                    'id_finalidad_11',
                    'id_finalidad_11_bis',
                    'col_az',

                    'id_accion_12',
                    'id_finalidad_12',
                    'id_finalidad_12_bis',
                    'col_bd',

                    'id_accion_13',
                    'id_finalidad_13',
                    'id_finalidad_13_bis',
                    'col_bh',

                    'id_accion_14',
                    'id_finalidad_14',
                    'id_finalidad_14_bis',
                    'col_bl',

                    'id_accion_15',
                    'id_finalidad_15',
                    'id_finalidad_15_bis',
                    'col_bp',

                    'id_accion_16',
                    'id_finalidad_16',
                    'id_finalidad_16_bis',
                    'col_bt',

                    'id_accion_17',
                    'id_finalidad_17',
                    'id_finalidad_17_bis',
                    'col_bx',

                    'id_accion_18',
                    'id_finalidad_18',
                    'id_finalidad_18_bis',
                    'col_cb',

                    'id_accion_19',
                    'id_finalidad_19',
                    'id_finalidad_19_bis',
                    'col_cf',

                    'id_accion_20',
                    'id_finalidad_20',
                    'id_finalidad_20_bis',
                    'col_cj',

                    'col_ck',
                    'codigo_claves_de_acciones_de_capacitacion',
                    'contador',
                ];

                foreach ($camposACopiar as $campo) {
                    if (property_exists($datosBase, $campo)) {
                        $insertCap[$campo] = $datosBase->$campo;
                    }
                }
            }

            // Insertar empleado en plantilla
            DB::table('public.a2_acciones_capacitacion')->insert($insertCap);

            // =========================================================================
            // 6) Insertar cursos base en public.a2_acciones_empleados
            //    Cursos obligatorios: 1000001 y 1000002
            // =========================================================================
            $configCursos = [
                [
                    'id_accion'    => 1000001,
                    'id_finalidad' => 3,
                ],
                [
                    'id_accion'    => 1000002,
                    'id_finalidad' => 6,
                ],
            ];

            $maxIdEmpl = DB::table('public.a2_acciones_empleados')
                ->max('id_empl_accion') ?? 0;

            $cursosInsertados = [];

            foreach ($configCursos as $cfg) {
                // Datos de la acción
                $accion = DB::table('public.a1_cat_acciones')
                    ->select('duracion_hrs', 'tematica')
                    ->where('id_accion', $cfg['id_accion'])
                    ->first();

                if (! $accion) {
                    Log::warning('Curso obligatorio no encontrado en a1_cat_acciones al alta de empleado; se insertará sin datos de catálogo.', [
                        'curp' => $curpNuevo,
                        'id_accion' => $cfg['id_accion'],
                    ]);
                }

                // id_tematica según la temática de la acción
                $idTematica = null;

                if (!empty($accion?->tematica)) {
                    $idTematica = DB::table('public.cat_tematica')
                        ->whereRaw('TRIM(UPPER(tematica)) = TRIM(UPPER(?))', [$accion->tematica])
                        ->value('id_tematica');
                }

                // Consecutivo de curso para este empleado
                $maxNumCurso = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('UPPER(TRIM(curp)) = ?', [$curpNuevo])
                    ->max('id_num_curso');

                $nextNumCurso = $maxNumCurso ? $maxNumCurso + 1 : 1;

                // Nuevo id_empl_accion
                $maxIdEmpl++;

                $insertCurso = [
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
                    'observaciones'    => self::OBSERVACION_CURSO_OBLIGATORIO,
                    'id_cat_estatus'   => null,
                    'id_cat_tematica'  => $idTematica,
                    'horas_progamadas' => $accion?->duracion_hrs,
                ];

                DB::table('public.a2_acciones_empleados')->insert($insertCurso);

                $cursosInsertados[] = $insertCurso;
            }

            DB::commit();

            UserActionLogger::write(
                idUsuario: auth()->id() ? (int) auth()->id() : null,
                modulo: 'EMPLEADOS',
                accion: 'CREAR_EMPLEADO',
                descripcion: 'Alta de empleado y cursos base.',
                idReferencia: $curpNuevo,
                payload: [
                    'id_cat' => (int) $nextIdCat,
                    'id_puesto' => $nextIdPuesto,
                    'curp' => $curpNuevo,
                    'catalogos' => [
                        'codigo_puesto' => $puestoCatalogo->codigo_puesto,
                        'clave_clues' => $cluesCatalogo->clave_clues,
                        'id_clues' => $cluesCatalogo->id_clues ?? null,
                    ],
                    'cursos_base' => array_map(
                        fn ($curso) => [
                            'id_empl_accion' => $curso['id_empl_accion'],
                            'id_accion' => $curso['id_accion'],
                            'id_num_curso' => $curso['id_num_curso'],
                        ],
                        $cursosInsertados
                    ),
                ],
                newValues: [
                    'plantilla' => $insertCap,
                    'cursos_base' => $cursosInsertados,
                ]
            );

            return redirect()
                ->route('empleado')
                ->with('success', 'Empleado y cursos base agregados correctamente. CURP: ' . $curpNuevo);

        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

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

    private function sexoFromCurp(string $curp): ?string
    {
        $sexo = substr(EmpleadoCatalogs::norm($curp), 10, 1);

        return match ($sexo) {
            'H' => 'HOMBRE',
            'M' => 'MUJER',
            default => null,
        };
    }
}
