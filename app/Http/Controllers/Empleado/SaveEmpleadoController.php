<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Support\AdscripcionCatalogs;
use App\Support\EmpleadoCatalogs;
use App\Support\PacVisibility;
use App\Support\UserActionLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SaveEmpleadoController extends Controller
{
    private const CURP_BASE_PLANTILLA = 'OIJN850210MMCRMN07';
    private const OBSERVACION_CURSO_OBLIGATORIO = 'OBLIGATORIO';

    public function save(Request $request)
    {
        $request->merge([
            'curp' => EmpleadoCatalogs::norm($request->input('curp')),
            'rfc' => $request->filled('rfc') ? EmpleadoCatalogs::norm($request->input('rfc')) : null,
            'sexo' => $request->filled('sexo') ? EmpleadoCatalogs::norm($request->input('sexo')) : null,
            'nombre' => EmpleadoCatalogs::norm($request->input('nombre')),
            'apellido_paterno' => EmpleadoCatalogs::norm($request->input('apellido_paterno')),
            'apellido_materno' => $request->filled('apellido_materno') ? EmpleadoCatalogs::norm($request->input('apellido_materno')) : null,
            'tipo_contratacion' => $request->filled('tipo_contratacion') ? EmpleadoCatalogs::norm($request->input('tipo_contratacion')) : null,
            'nomina' => $request->filled('nomina') ? EmpleadoCatalogs::norm($request->input('nomina')) : null,
            'nomina_dos' => $request->filled('nomina_dos') ? EmpleadoCatalogs::norm($request->input('nomina_dos')) : null,
            'nivel_atencion' => $request->filled('nivel_atencion') ? EmpleadoCatalogs::norm($request->input('nivel_atencion')) : null,
            'entidad' => $request->filled('entidad') ? EmpleadoCatalogs::norm($request->input('entidad')) : null,
            'codigo_puesto' => EmpleadoCatalogs::norm($request->input('codigo_puesto')),
            'clave_clues' => EmpleadoCatalogs::norm($request->input('clave_clues')),
            'descripcion_clues' => $request->filled('descripcion_clues') ? EmpleadoCatalogs::norm($request->input('descripcion_clues')) : null,
            'clues_manual' => $request->filled('clues_manual') ? (string) $request->input('clues_manual') : '0',
            'val_plantilla' => EmpleadoCatalogs::norm($request->input('val_plantilla')),
            'observaciones_plantilla' => EmpleadoCatalogs::norm($request->input('observaciones_plantilla')),
            'nombre_unidad' => $request->filled('nombre_unidad') ? AdscripcionCatalogs::norm($request->input('nombre_unidad')) : null,
            'nombre_coordinacion' => $request->filled('nombre_coordinacion') ? AdscripcionCatalogs::norm($request->input('nombre_coordinacion')) : null,
        ]);

        // 1) Validación
        $validated = $request->validate([
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
            'nomina_dos'        => 'nullable|string|max:100',
            'nivel_atencion'    => 'nullable|string|max:50',
            'entidad'           => 'nullable|string|max:100',
            'clues_catalog_key'  => 'required|string|max:2000',
            'id_clues'          => 'nullable|integer',
            'clues_manual'      => 'nullable|in:0,1',
            'clave_clues'       => 'required|string|max:50',
            'descripcion_clues' => 'required|string|max:255',
            'quincena'          => 'nullable|integer|min:1|max:24',
            'val_plantilla'     => 'required|string|max:100',
            'observaciones_plantilla' => 'required|string|max:1000',
            'id_adscripcion'    => 'required|integer|min:1',
            'id_unidad'         => 'nullable|integer',
            'nombre_unidad'     => 'nullable|string|max:200',
            'id_coordinacion'   => 'nullable|integer',
            'nombre_coordinacion' => 'nullable|string|max:250',
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
            'descripcion_clues.required' => 'Captura la descripcion de la CLUES.',
            'val_plantilla.required' => 'El campo Val Plantilla es obligatorio.',
            'observaciones_plantilla.required' => 'El campo Observaciones Plantilla es obligatorio.',
            'id_adscripcion.required' => 'Selecciona una Adscripcion del catalogo.',
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
            $cluesManual = false;

            if (! $cluesCatalogo) {
                $cluesCatalogo = EmpleadoCatalogs::manualCluesFromValues(
                    $validated['clave_clues'],
                    $validated['descripcion_clues'],
                    $validated['nomina'] ?? null,
                    $validated['entidad'] ?? null,
                    $validated['nivel_atencion'] ?? null
                );
                $cluesManual = (bool) $cluesCatalogo;
            }

            if (! $cluesCatalogo) {
                throw ValidationException::withMessages([
                    'clave_clues' => 'La CLUES seleccionada no existe en el catálogo.',
                ]);
            }

            $adscripcionCatalogo = AdscripcionCatalogs::findById($validated['id_adscripcion']);

            if (! $adscripcionCatalogo) {
                throw ValidationException::withMessages([
                    'id_adscripcion' => 'La Adscripcion seleccionada no existe en el catalogo oficial.',
                ]);
            }

            $idAdscripcionScope = PacVisibility::adminAdscripcionScope(auth()->user());

            if ($idAdscripcionScope !== null && (int) $adscripcionCatalogo->id_adscripcion !== $idAdscripcionScope) {
                throw ValidationException::withMessages([
                    'id_adscripcion' => 'No tienes permiso para usar una adscripcion fuera de tu alcance.',
                ]);
            }

            if (! empty($validated['nomina_dos'])) {
                if (! $this->capacitacionHasColumn('nomina_dos')) {
                    throw ValidationException::withMessages([
                        'nomina_dos' => 'La columna Nomina Dos no existe en la tabla de plantilla.',
                    ]);
                }

                if (! EmpleadoCatalogs::nominaDosOptions()->contains($validated['nomina_dos'])) {
                    throw ValidationException::withMessages([
                        'nomina_dos' => 'Selecciona un valor valido de Nomina Dos.',
                    ]);
                }
            }

            if (! $this->capacitacionHasColumn('val_plantilla')) {
                throw ValidationException::withMessages([
                    'val_plantilla' => 'La columna Val Plantilla no existe en la tabla de plantilla.',
                ]);
            }

            if (! $this->capacitacionHasColumn('observaciones_plantilla')) {
                throw ValidationException::withMessages([
                    'observaciones_plantilla' => 'La columna Observaciones Plantilla no existe en la tabla de plantilla. Ejecuta las migraciones pendientes.',
                ]);
            }

            DB::beginTransaction();

            // CURP base por defecto, aunque no venga en el formulario.
            // Se usa siempre OIJN850210MMCRMN07 como plantilla.
            $curpBase = self::CURP_BASE_PLANTILLA;

            /*
            |--------------------------------------------------------------------------
            | Bloqueo de alta de empleado
            |--------------------------------------------------------------------------
            | Este flujo calcula ids con MAX + 1 por compatibilidad con tablas
            | existentes. El advisory lock evita duplicados si dos usuarios dan de
            | alta empleados al mismo tiempo dentro de PostgreSQL.
            */
            DB::statement('SELECT pg_advisory_xact_lock(2026071401)');
            DB::statement('LOCK TABLE public.a2_acciones_capacitacion IN ACCESS EXCLUSIVE MODE');
            DB::statement('LOCK TABLE public.a2_acciones_empleados IN ACCESS EXCLUSIVE MODE');

            if ($cluesManual) {
                $cluesCatalogo = EmpleadoCatalogs::ensureManualClues(
                    $validated['clave_clues'],
                    $validated['descripcion_clues'],
                    $validated['nomina'] ?? null,
                    $validated['entidad'] ?? null,
                    $validated['nivel_atencion'] ?? null
                );

                if (! $cluesCatalogo) {
                    throw ValidationException::withMessages([
                        'clave_clues' => 'No se pudo agregar la CLUES al catalogo.',
                    ]);
                }
            }

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

            $nivelAtencion = !empty($validated['nivel_atencion'])
                ? $validated['nivel_atencion']
                : (($cluesCatalogo->nivel_atencion ?? '') !== ''
                    ? $cluesCatalogo->nivel_atencion
                    : ($datosBase->nivel_atencion ?? null));

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
                                        ? $validated['rfc']
                                        : null,

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

                'nivel_atencion'    => $nivelAtencion,

                'entidad'           => $entidad,
                'val_plantilla'     => $validated['val_plantilla'],
                'observaciones_plantilla' => $validated['observaciones_plantilla'],
                'num_cursos'        => (int) $adscripcionCatalogo->id_adscripcion,
                'activo'            => 2,
                'id_unidad'         => $adscripcionCatalogo->id_unidad,
                'id_coordinacion'   => $adscripcionCatalogo->id_coordinacion,
            ];

            if ($this->capacitacionHasColumn('nomina_dos')) {
                $insertCap['nomina_dos'] = !empty($validated['nomina_dos'])
                    ? $validated['nomina_dos']
                    : ($datosBase->nomina_dos ?? null);
            }

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

            $vinculosEmpleado = $this->vincularRegistrosEmpleado($curpNuevo, (int) $nextIdCat);

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
                        'id_adscripcion' => (int) $adscripcionCatalogo->id_adscripcion,
                        'adscripcion' => $adscripcionCatalogo->adscripcion,
                        'id_unidad' => $adscripcionCatalogo->id_unidad,
                        'id_coordinacion' => $adscripcionCatalogo->id_coordinacion,
                        'nomina_dos' => $insertCap['nomina_dos'] ?? null,
                    ],
                    'cursos_base' => array_map(
                        fn ($curso) => [
                            'id_empl_accion' => $curso['id_empl_accion'],
                            'id_accion' => $curso['id_accion'],
                            'id_num_curso' => $curso['id_num_curso'],
                        ],
                        $cursosInsertados
                    ),
                    'vinculos_empleado' => $vinculosEmpleado,
                ],
                newValues: [
                    'plantilla' => $insertCap,
                    'cursos_base' => $cursosInsertados,
                    'vinculos_empleado' => $vinculosEmpleado,
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

    private function capacitacionHasColumn(string $column): bool
    {
        try {
            return DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', 'a2_acciones_capacitacion')
                ->where('column_name', $column)
                ->exists();
        } catch (\Throwable $th) {
            Log::error('Error al validar columna de a2_acciones_capacitacion', [
                'column' => $column,
                'error' => $th->getMessage(),
            ]);

            return false;
        }
    }

    private function vincularRegistrosEmpleado(string $curp, int $idCat): array
    {
        if ($idCat <= 0) {
            throw ValidationException::withMessages([
                'general' => 'No se obtuvo un id_cat valido para vincular constancias y tokens.',
            ]);
        }

        $resumen = [];

        foreach ([
            'tbl_constancias' => 'constancias',
            'tbl_registros_token' => 'registros_token',
        ] as $table => $label) {
            $this->assertEmployeeLinkTableReady($table);

            $registros = DB::table("public.{$table}")
                ->select('id_empleado')
                ->whereRaw('UPPER(BTRIM(curp::text)) = UPPER(BTRIM(?))', [$curp])
                ->lockForUpdate()
                ->get();

            $actualizados = DB::table("public.{$table}")
                ->whereRaw('UPPER(BTRIM(curp::text)) = UPPER(BTRIM(?))', [$curp])
                ->update([
                    'id_empleado' => $idCat,
                ]);

            $resumen[$label] = [
                'tabla' => "public.{$table}",
                'coincidencias' => $registros->count(),
                'actualizados' => (int) $actualizados,
            ];
        }

        return $resumen;
    }

    private function assertEmployeeLinkTableReady(string $table): void
    {
        if (! $this->publicTableExists($table)) {
            throw ValidationException::withMessages([
                'general' => "La tabla public.{$table} no existe para vincular el empleado.",
            ]);
        }

        foreach (['curp', 'id_empleado'] as $column) {
            if (! $this->publicColumnExists($table, $column)) {
                throw ValidationException::withMessages([
                    'general' => "La columna {$column} no existe en public.{$table}.",
                ]);
            }
        }
    }

    private function publicTableExists(string $table): bool
    {
        try {
            return DB::table('information_schema.tables')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->exists();
        } catch (\Throwable $th) {
            Log::error('Error al validar tabla publica para alta de empleado', [
                'table' => $table,
                'error' => $th->getMessage(),
            ]);

            return false;
        }
    }

    private function publicColumnExists(string $table, string $column): bool
    {
        try {
            return DB::table('information_schema.columns')
                ->where('table_schema', 'public')
                ->where('table_name', $table)
                ->where('column_name', $column)
                ->exists();
        } catch (\Throwable $th) {
            Log::error('Error al validar columna publica para alta de empleado', [
                'table' => $table,
                'column' => $column,
                'error' => $th->getMessage(),
            ]);

            return false;
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
