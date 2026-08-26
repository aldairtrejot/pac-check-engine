<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Models\Pac\Helpers\GetTrimestreModel;
use App\Services\Constancias\ConstanciaNotificationService;
use App\Support\ConstanciaVisibilityByName;
use App\Support\UserActionLogger;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class UpdateEstatusConstanciasController extends Controller
{
    private const CONST_PENDIENTE = 1;
    private const CONST_CONCLUIDO = 2;
    private const CONST_RECHAZADO = 3;

    /**
     * En public.cat_estatus:
     * 1 = VIGENTE
     * 2 = ALTA
     * 3 = BAJA
     * 4 = NO VIGENTE
     */
    private const PLANTILLA_ID_CAT_ESTATUS_DEFAULT = 1;
    private const PLANTILLA_ID_CAT_ESTATUS_NO_VIGENTE_DEFAULT = 4;

    private const CURSO_OBLIGATORIO_BASE_ID = 1000001;
    private const CURSO_CIBERSEGURIDAD_ID = 1000011;
    private const OBSERVACION_ANULACION_CURSO_BASE =
        'ANULADO AUTOMÁTICAMENTE POR ACEPTACIÓN DEL CURSO 1000011';

    public function update(Request $request)
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No autenticado.',
                ], 401);
            }

            $request->validate([
                'id_respuesta'        => 'required',
                'accion'              => 'required|string',
                'motivo'              => 'nullable|string|max:2000',
                'confirmar_duplicado' => 'nullable|boolean',
                'rechazar_duplicado'  => 'nullable|boolean',
            ]);

            $idRespuesta = trim((string) $request->input('id_respuesta'));
            $accion      = strtoupper(trim((string) $request->input('accion')));
            $motivo      = trim((string) $request->input('motivo', ''));

            $confirmarDuplicado = filter_var(
                $request->input('confirmar_duplicado', false),
                FILTER_VALIDATE_BOOLEAN
            );

            $rechazarDuplicado = filter_var(
                $request->input('rechazar_duplicado', false),
                FILTER_VALIDATE_BOOLEAN
            );

            if ($idRespuesta === '') {
                return response()->json([
                    'status'  => false,
                    'message' => 'id_respuesta inválido.',
                ], 422);
            }

            if (! in_array($accion, ['ACEPTAR', 'RECHAZAR'], true)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Acción inválida.',
                ], 422);
            }

            if ($accion === 'RECHAZAR' && $motivo === '') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Debes capturar el motivo del rechazo.',
                ], 422);
            }

            if ($accion === 'RECHAZAR') {
                return $this->rejectConstancia($idRespuesta, $motivo, $user);
            }

            return $this->acceptConstancia(
                idRespuesta: $idRespuesta,
                user: $user,
                confirmarDuplicado: $confirmarDuplicado,
                rechazarDuplicado: $rechazarDuplicado
            );

        } catch (\Throwable $th) {
            Log::error('Constancias update error: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al procesar el registro.',
            ], 500);
        }
    }

    private function rejectConstancia(string $idRespuesta, string $motivo, $user)
    {
        $colsConstancias = $this->columnsFor('public', 'tbl_constancias');
        $colSetConst = array_flip($colsConstancias);

        $motivoColumn = $this->firstExistingColumnFromSet($colSetConst, [
            'motivo_rechazo',
            'motivo',
            'observaciones',
            'comentarios',
        ]);

        $fechaRechazoColumn = $this->firstExistingColumnFromSet($colSetConst, [
            'fecha_rechazo',
            'fecha_rechazo_at',
        ]);

        $resultado = DB::transaction(function () use ($idRespuesta, $motivo, $motivoColumn, $fechaRechazoColumn, $user) {
            $registro = $this->baseConstanciaOnlyQuery($user)
                ->select([
                    'c.id_respuesta',
                    'c.curp',
                    'c.nombre_curso',
                    'c.correo_electronico',
                    'c.fecha_ultima_accion',
                    'c.estatus',
                    'c.id_puesto',
                ])
                ->where('c.id_respuesta', $idRespuesta)
                ->lockForUpdate()
                ->first();

            if (! $registro) {
                return [
                    'status'  => false,
                    'code'    => 404,
                    'message' => 'Registro no encontrado, fuera de tu alcance o no válido para revisión.',
                ];
            }

            if ((int) $registro->estatus !== self::CONST_PENDIENTE) {
                return [
                    'status'  => false,
                    'code'    => 409,
                    'message' => 'La constancia ya fue procesada previamente con estatus: ' . $this->statusLabel((int) $registro->estatus) . '.',
                ];
            }

            $updateData = [
                'estatus'             => self::CONST_RECHAZADO,
                'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_TIMESTAMP)"),
                'fecha_ultima_accion' => DB::raw("CURRENT_TIMESTAMP"),
            ];

            if ($motivoColumn) {
                $updateData[$motivoColumn] = $motivo;
            }

            if ($fechaRechazoColumn) {
                $updateData[$fechaRechazoColumn] = DB::raw("CURRENT_TIMESTAMP");
            }

            $updated = DB::table('public.tbl_constancias')
                ->where('id_respuesta', $idRespuesta)
                ->where('estatus', self::CONST_PENDIENTE)
                ->update($updateData);

            if ($updated < 1) {
                return [
                    'status'  => false,
                    'code'    => 409,
                    'message' => 'La constancia cambió de estatus mientras se procesaba. Intenta recargar la tabla.',
                ];
            }

            $notify = $this->notificationDataForConstancia($idRespuesta, $user);

            return [
                'status'             => true,
                'curp'               => (string) ($registro->curp ?? ''),
                'correo_electronico' => (string) ($notify['correo_electronico'] ?? ''),
                'nombre_persona'     => (string) ($notify['nombre_persona'] ?? ''),
                'nombre_curso'       => (string) ($notify['nombre_curso'] ?? ''),
                'folio'              => (string) ($notify['folio'] ?? $idRespuesta),
            ];
        });

        if (! $resultado['status']) {
            return response()->json([
                'status'  => false,
                'message' => $resultado['message'],
            ], $resultado['code'] ?? 422);
        }

        $ahora = Carbon::now('America/Mexico_City');

        $emailEnviado = $this->sendConstanciaDecisionEmail(
            correoDestinatario: (string) ($resultado['correo_electronico'] ?? ''),
            nombrePersona: (string) ($resultado['nombre_persona'] ?? ''),
            nombreCurso: (string) ($resultado['nombre_curso'] ?? ''),
            folio: (string) ($resultado['folio'] ?? ''),
            fechaHora: $ahora->format('d/m/Y H:i:s'),
            tipo: 'rechazo',
            motivo: $motivo,
            curp: (string) ($resultado['curp'] ?? '')
        );

        UserActionLogger::write(
            idUsuario: (int) $user->id,
            modulo: 'CONSTANCIAS',
            accion: 'RECHAZAR_CONSTANCIA',
            descripcion: 'Rechazo de constancia validada por usuario.',
            idReferencia: $idRespuesta,
            payload: [
                'folio' => (string) ($resultado['folio'] ?? $idRespuesta),
                'curp' => (string) ($resultado['curp'] ?? ''),
                'nombre_curso' => (string) ($resultado['nombre_curso'] ?? ''),
                'correo_electronico' => (string) ($resultado['correo_electronico'] ?? ''),
                'correo_enviado' => $emailEnviado,
                'motivo' => $motivo,
            ],
            oldValues: ['estatus' => self::CONST_PENDIENTE],
            newValues: ['estatus' => self::CONST_RECHAZADO]
        );

        return response()->json([
            'status'  => true,
            'message' => $emailEnviado
                ? 'Constancia rechazada y correo enviado correctamente.'
                : 'Constancia rechazada correctamente. No fue posible enviar el correo.',
        ], 200);
    }

    private function acceptConstancia(
        string $idRespuesta,
        $user,
        bool $confirmarDuplicado = false,
        bool $rechazarDuplicado = false
    ) {
        $resultado = DB::transaction(function () use ($idRespuesta, $user, $confirmarDuplicado, $rechazarDuplicado) {
            $c = $this->baseConstanciaOnlyQuery($user)
                ->select([
                    'c.id_respuesta',
                    'c.curp',
                    'c.nombre_curso',
                    'c.id_puesto',
                    'c.fecha_inicio',
                    'c.fecha_final',
                    'c.horas_realizadas',
                    'c.calificacion_n',
                    'c.instancia',
                    'c.instancia_otro',
                    'c.correo_electronico',
                    'c.estatus',
                ])
                ->where('c.id_respuesta', $idRespuesta)
                ->lockForUpdate()
                ->first();

            if (! $c) {
                return [
                    'status'  => false,
                    'code'    => 404,
                    'message' => 'Registro de constancia no encontrado, fuera de tu alcance o no válido para revisión.',
                ];
            }

            if ((int) $c->estatus !== self::CONST_PENDIENTE) {
                return [
                    'status'  => false,
                    'code'    => 409,
                    'message' => 'La constancia ya fue procesada previamente con estatus: ' . $this->statusLabel((int) $c->estatus) . '.',
                ];
            }

            $curp        = trim((string) ($c->curp ?? ''));
            $cursoTxt    = trim((string) ($c->nombre_curso ?? ''));
            $idPuestoTxt = trim((string) ($c->id_puesto ?? ''));

            if ($curp === '' || $cursoTxt === '' || $idPuestoTxt === '') {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => 'Faltan datos en la constancia (CURP, curso o id_puesto).',
                ];
            }

            $accionCat = DB::table('public.a1_cat_acciones')
                ->select(['id_accion', 'duracion_hrs'])
                ->whereRaw('TRIM(UPPER(nombre_accion)) = TRIM(UPPER(?))', [$cursoTxt])
                ->first();

            if (! $accionCat) {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => 'El curso NO existe en el catálogo (a1_cat_acciones). No se puede procesar.',
                ];
            }

            $idAccion = (int) $accionCat->id_accion;

            /*
            |--------------------------------------------------------------------------
            | Confirmación rápida de constancia duplicada
            |--------------------------------------------------------------------------
            | Esta validación se realiza antes de resolver horas, instancia, temática,
            | trimestre y calificación para que el SweetAlert2 aparezca de inmediato.
            */
            DB::select("SELECT pg_advisory_xact_lock(5001)");
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$curp]);

            $yaConcluido = $this->actionAlreadyConcludedForEmployee(
                idPuestoTxt: $idPuestoTxt,
                curp: $curp,
                idAccion: $idAccion
            );

            if ($yaConcluido && ! $confirmarDuplicado) {
                if ($rechazarDuplicado) {
                    return $this->rejectDuplicatedConstanciaInsideTransaction(
                        idRespuesta: $idRespuesta,
                        user: $user,
                        curp: $curp
                    );
                }

                return [
                    'status'                => false,
                    'code'                  => 200,
                    'requires_confirmation' => true,
                    'duplicate_concluido'   => true,
                    'message'               => 'Este curso ya se encuentra concluido para el trabajador. Si seleccionas Continuar, la información será actualizada o se realizará nuevamente el proceso de generación de la constancia. Si seleccionas Cancelar/Rechazar, se enviará un correo informando que la constancia ya había sido generada y enviada con anterioridad.',
                ];
            }

            $colsEmp = $this->columnsFor('public', 'a2_acciones_empleados');
            $colSet  = array_flip($colsEmp);

            $hasCalificacion = isset($colSet['calificacion']);
            $hasHorasProg    = isset($colSet['horas_progamadas']);
            $hasEvalApr      = isset($colSet['eval_aprendizaje']);

            $idCatEstatusVigente = $this->resolveIdCatEstatusVigente();

            /*
            |--------------------------------------------------------------------------
            | Horas programadas
            |--------------------------------------------------------------------------
            | 1. Primero toma duracion_hrs desde a1_cat_acciones.
            | 2. Si viene vacío, busca horas_progamadas en a2_acciones_empleados.
            | 3. Si tampoco encuentra, busca horas_real existentes.
            | 4. Si tampoco encuentra, usa horas_realizadas de la constancia.
            */
            $horasProgramadas = $this->resolveHorasProgramadasSafe(
                idAccion: $idAccion,
                horasCatalogo: $accionCat->duracion_hrs ?? null,
                horasConstancia: $c->horas_realizadas ?? null
            );

            $instTxt = trim((string) (($c->instancia ?? '') !== '' ? $c->instancia : ($c->instancia_otro ?? '')));
            $idInstancia = $this->resolveIdInstanciaSafe($instTxt);

            if (! $idInstancia) {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => "No se pudo mapear la instancia '{$instTxt}' a un id_instancia del catálogo.",
                ];
            }

            $tematicaTxt = $this->resolveTematicaFromCursoSafe($idAccion);
            if (! $tematicaTxt) {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => 'No se pudo obtener la temática asociada al curso desde el catálogo.',
                ];
            }

            $idTematica = $this->resolveIdTematicaSafe($tematicaTxt);
            if (! $idTematica) {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => "No se pudo mapear la temática '{$tematicaTxt}' a cat_tematica.",
                ];
            }

            $idTrimestre = null;
            if (! empty($c->fecha_inicio)) {
                $idTrimestre = (new GetTrimestreModel())->getTrimestre((string) $c->fecha_inicio);
                $idTrimestre = ! empty($idTrimestre) ? (int) $idTrimestre : null;
            }

            if (empty($c->fecha_inicio) || empty($c->fecha_final) || empty($idTrimestre)) {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => 'La constancia no trae fechas suficientes (fecha_inicio/fecha_final).',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Horas realizadas / horas_real
            |--------------------------------------------------------------------------
            | Regla solicitada:
            | 1. Recuperar horas del catálogo a1_cat_acciones.duracion_hrs.
            | 2. Compararlas contra tbl_constancias.horas_realizadas.
            | 3. Si horas_realizadas viene vacía, nula, inválida, en cero o mayor
            |    a la duración del catálogo, guardar la duración del catálogo.
            | 4. Si horas_realizadas viene válida y es menor o igual al catálogo,
            |    guardar horas_realizadas.
            |
            | Nota: si el catálogo no trae duración válida, se conserva la hora
            | válida de la constancia como respaldo.
            */
            $horasReal = $this->resolveHorasRealSafe(
                horasCatalogo: $accionCat->duracion_hrs ?? null,
                horasConstancia: $c->horas_realizadas ?? null
            );

            /*
            |--------------------------------------------------------------------------
            | Calificación
            |--------------------------------------------------------------------------
            | La columna anterior era: calificacion
            | La columna nueva es: calificacion_n
            |
            | Esta calificación es la que se copia a:
            | public.a2_acciones_empleados.calificacion
            */
            $cal = 100.00;
            $calRaw = trim((string) ($c->calificacion_n ?? ''));

            if ($calRaw !== '') {
                $calRaw = str_replace(',', '.', $calRaw);

                if (is_numeric($calRaw)) {
                    $cal = round((float) $calRaw, 2);
                }
            }

            if ($cal < 70) {
                $cal = 70.00;
            }

            if ($cal > 100) {
                $cal = 100.00;
            }

            $calTexto = rtrim(rtrim(number_format($cal, 2, '.', ''), '0'), '.');

            $cursoBaseAnulado = null;
            if ($idAccion === self::CURSO_CIBERSEGURIDAD_ID) {
                $cursoBaseAnulado = $this->anularCursoBasePendientePorCiberseguridad(
                    idPuestoTxt: $idPuestoTxt,
                    curp: $curp,
                    idCatEstatusNoVigente: $this->resolveIdCatEstatusNoVigente()
                );
            }

            $existe = DB::table('public.a2_acciones_empleados')
                ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                ->where('id_accion', $idAccion)
                ->exists();

            /*
            |--------------------------------------------------------------------------
            | Observaciones
            |--------------------------------------------------------------------------
            | El flujo correcto es:
            | 1. La constancia trae el nombre del curso en c.nombre_curso.
            | 2. Ese nombre se busca en public.a1_cat_acciones.nombre_accion.
            | 3. De ahí se obtiene el id_accion real del catálogo.
            | 4. Con ese id_accion se define el texto de observaciones:
            |    - 1 o 2                 => PAC 2025
            |    - 1000001, 1000002 o 1000011 => OBLIGATORIO
            |    - Cualquier otro        => CURSO EXTRA
            |
            | Todos los textos se guardan en MAYÚSCULAS.
            */
            $obsBase = $this->resolveObservacionesBase($idAccion);

            if ($existe) {
                $upd = [
                    'id_finalidad'     => 6,
                    'id_cat_estatus'   => $idCatEstatusVigente,
                    'fecha_ini'        => $c->fecha_inicio,
                    'fecha_fin'        => $c->fecha_final,
                    'id_trimestre'     => $idTrimestre,
                    'id_instancia'     => (string) $idInstancia,
                    'id_cat_tematica'  => (string) $idTematica,
                    'horas_real'       => $horasReal,
                ];

                /*
                |--------------------------------------------------------------------------
                | Observaciones en update
                |--------------------------------------------------------------------------
                | Se fuerza siempre el texto correcto según el id_accion resuelto desde
                | el nombre del curso. Esto evita conservar observaciones anteriores con
                | formato viejo como "Curso Extra." o textos manuales.
                */
                $upd['observaciones'] = $obsBase;

                if ($hasEvalApr) {
                    $upd['eval_aprendizaje'] = true;
                }

                if ($hasHorasProg) {
                    $upd['horas_progamadas'] = $horasProgramadas;
                }

                if ($hasCalificacion) {
                    $upd['calificacion'] = $cal;
                }

                DB::table('public.a2_acciones_empleados')
                    ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                    ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                    ->where('id_accion', $idAccion)
                    ->update($upd);

            } else {
                $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                $newId = $maxId ? ((int) $maxId + 1) : 1;

                $maxNumCurso = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                    ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                    ->max('id_num_curso');

                $nextNumCurso = $maxNumCurso ? ((int) $maxNumCurso + 1) : 1;

                $ins = [
                    'id_empl_accion'   => $newId,
                    'id_puesto'        => $idPuestoTxt,
                    'curp'             => $curp,
                    'id_accion'        => $idAccion,
                    'id_finalidad'     => 6,
                    'horas_real'       => $horasReal,
                    'id_instancia'     => (string) $idInstancia,
                    'costo_unitario'   => null,
                    'fecha_ini'        => $c->fecha_inicio,
                    'fecha_fin'        => $c->fecha_final,
                    'id_trimestre'     => $idTrimestre,
                    'id_num_curso'     => $nextNumCurso,
                    'observaciones'    => $obsBase,
                    'id_cat_estatus'   => $idCatEstatusVigente,
                    'id_cat_tematica'  => (string) $idTematica,
                ];

                if ($hasEvalApr) {
                    $ins['eval_aprendizaje'] = true;
                }

                if ($hasHorasProg) {
                    $ins['horas_progamadas'] = $horasProgramadas;
                }

                if ($hasCalificacion) {
                    $ins['calificacion'] = $cal;
                }

                DB::table('public.a2_acciones_empleados')->insert($ins);
            }

            $hasFechaHoraEnvioColumn = in_array(
                'fecha_hora_envio',
                $this->columnsFor('public', 'tbl_constancias'),
                true
            );

            $updateConstanciaData = [
                'estatus'             => self::CONST_CONCLUIDO,
                'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_TIMESTAMP)"),
                'fecha_ultima_accion' => DB::raw("CURRENT_TIMESTAMP"),
            ];

            if ($hasFechaHoraEnvioColumn) {
                $updateConstanciaData['fecha_hora_envio'] = DB::raw("CURRENT_TIMESTAMP");
            }

            $updated = DB::table('public.tbl_constancias')
                ->where('id_respuesta', $idRespuesta)
                ->where('estatus', self::CONST_PENDIENTE)
                ->update($updateConstanciaData);

            if ($updated < 1) {
                return [
                    'status'  => false,
                    'code'    => 409,
                    'message' => 'La constancia cambió de estatus mientras se procesaba. Intenta recargar la tabla.',
                ];
            }

            $fechaHoraEnvio = $hasFechaHoraEnvioColumn
                ? DB::table('public.tbl_constancias')
                    ->where('id_respuesta', $idRespuesta)
                    ->value('fecha_hora_envio')
                : null;

            $notify = $this->notificationDataForConstancia($idRespuesta, $user);

            return [
                'status'             => true,
                'message'            => 'Constancia aceptada y procesada correctamente.',
                'curp'               => (string) ($c->curp ?? ''),
                'correo_electronico' => (string) ($notify['correo_electronico'] ?? ''),
                'nombre_persona'     => (string) ($notify['nombre_persona'] ?? ''),
                'nombre_curso'       => (string) ($notify['nombre_curso'] ?? ''),
                'folio'              => (string) ($notify['folio'] ?? $idRespuesta),
                'fecha_hora_envio'   => $fechaHoraEnvio ? (string) $fechaHoraEnvio : null,
                'curso_base_anulado' => $cursoBaseAnulado,
            ];
        });

        if (! $resultado['status']) {
            $response = [
                'status'  => false,
                'message' => $resultado['message'],
            ];

            if (! empty($resultado['requires_confirmation'])) {
                $response['requires_confirmation'] = true;
            }

            if (! empty($resultado['duplicate_concluido'])) {
                $response['duplicate_concluido'] = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Importante:
            |--------------------------------------------------------------------------
            | Cuando se detecta duplicidad, no debe regresar 409 porque no es un error
            | final. Es una confirmación que debe resolver el usuario con SweetAlert2.
            */
            $httpCode = ! empty($resultado['requires_confirmation'])
                ? 200
                : ($resultado['code'] ?? 422);

            return response()->json($response, $httpCode);
        }

        if (! empty($resultado['duplicate_rejected'])) {
            $ahora = Carbon::now('America/Mexico_City');

            $emailEnviado = $this->sendConstanciaDecisionEmail(
                correoDestinatario: (string) ($resultado['correo_electronico'] ?? ''),
                nombrePersona: (string) ($resultado['nombre_persona'] ?? ''),
                nombreCurso: (string) ($resultado['nombre_curso'] ?? ''),
                folio: (string) ($resultado['folio'] ?? ''),
                fechaHora: $ahora->format('d/m/Y H:i:s'),
                tipo: 'duplicada',
                motivo: (string) ($resultado['motivo'] ?? 'La constancia ya había sido generada y enviada con anterioridad.'),
                curp: (string) ($resultado['curp'] ?? '')
            );

            UserActionLogger::write(
                idUsuario: (int) $user->id,
                modulo: 'CONSTANCIAS',
                accion: 'RECHAZAR_CONSTANCIA_DUPLICADA',
                descripcion: 'Rechazo automático de constancia duplicada confirmado por usuario.',
                idReferencia: $idRespuesta,
                payload: [
                    'folio' => (string) ($resultado['folio'] ?? $idRespuesta),
                    'curp' => (string) ($resultado['curp'] ?? ''),
                    'nombre_curso' => (string) ($resultado['nombre_curso'] ?? ''),
                    'correo_electronico' => (string) ($resultado['correo_electronico'] ?? ''),
                    'correo_enviado' => $emailEnviado,
                    'motivo' => (string) ($resultado['motivo'] ?? ''),
                ],
                oldValues: ['estatus' => self::CONST_PENDIENTE],
                newValues: ['estatus' => self::CONST_RECHAZADO]
            );

            return response()->json([
                'status'  => true,
                'message' => $emailEnviado
                    ? 'Constancia duplicada rechazada y correo enviado correctamente.'
                    : 'Constancia duplicada rechazada correctamente. No fue posible enviar el correo.',
            ], 200);
        }

        $ahora = Carbon::now('America/Mexico_City');

        $emailEnviado = $this->sendConstanciaDecisionEmail(
            correoDestinatario: (string) ($resultado['correo_electronico'] ?? ''),
            nombrePersona: (string) ($resultado['nombre_persona'] ?? ''),
            nombreCurso: (string) ($resultado['nombre_curso'] ?? ''),
            folio: (string) ($resultado['folio'] ?? ''),
            fechaHora: $ahora->format('d/m/Y H:i:s'),
            tipo: 'aceptacion',
            motivo: null,
            curp: (string) ($resultado['curp'] ?? '')
        );

        UserActionLogger::write(
            idUsuario: (int) $user->id,
            modulo: 'CONSTANCIAS',
            accion: 'ACEPTAR_CONSTANCIA',
            descripcion: 'Aceptación de constancia y sincronización con plantilla PAC.',
            idReferencia: $idRespuesta,
            payload: [
                'folio' => (string) ($resultado['folio'] ?? $idRespuesta),
                'curp' => (string) ($resultado['curp'] ?? ''),
                'nombre_curso' => (string) ($resultado['nombre_curso'] ?? ''),
                'correo_electronico' => (string) ($resultado['correo_electronico'] ?? ''),
                'correo_enviado' => $emailEnviado,
                'fecha_hora_envio' => $resultado['fecha_hora_envio'] ?? null,
                'curso_base_anulado' => $resultado['curso_base_anulado'] ?? null,
            ],
            oldValues: ['estatus' => self::CONST_PENDIENTE],
            newValues: [
                'estatus' => self::CONST_CONCLUIDO,
                'fecha_hora_envio' => $resultado['fecha_hora_envio'] ?? null,
            ]
        );

        if (! empty($resultado['curso_base_anulado'])) {
            $cursoBaseAnulado = $resultado['curso_base_anulado'];

            UserActionLogger::write(
                idUsuario: (int) $user->id,
                modulo: 'CONSTANCIAS',
                accion: 'ANULAR_CURSO_1000001_POR_1000011',
                descripcion: 'Anulación automática del curso 1000001 pendiente al aceptar constancia del curso 1000011.',
                idReferencia: (string) ($cursoBaseAnulado['id_empl_accion'] ?? $idRespuesta),
                payload: [
                    'folio_constancia' => (string) ($resultado['folio'] ?? $idRespuesta),
                    'curp' => (string) ($resultado['curp'] ?? ''),
                    'id_puesto' => (string) ($cursoBaseAnulado['id_puesto'] ?? ''),
                    'ids_empl_accion_anulados' => $cursoBaseAnulado['ids_empl_accion'] ?? [],
                    'total_anulados' => $cursoBaseAnulado['total_anulados'] ?? 0,
                    'id_accion_anulada' => self::CURSO_OBLIGATORIO_BASE_ID,
                    'id_accion_aceptada' => self::CURSO_CIBERSEGURIDAD_ID,
                ],
                oldValues: [
                    'registros' => $cursoBaseAnulado['registros_anteriores'] ?? [],
                ],
                newValues: [
                    'id_cat_estatus' => $cursoBaseAnulado['estatus_nuevo'] ?? null,
                    'observaciones' => $cursoBaseAnulado['observaciones_nuevas'] ?? null,
                ]
            );
        }

        return response()->json([
            'status'  => true,
            'message' => $emailEnviado
                ? 'Constancia aceptada, procesada y correo enviado correctamente.'
                : 'Constancia aceptada y procesada correctamente. No fue posible enviar el correo.',
        ], 200);
    }

    private function notificationDataForConstancia(string $idRespuesta, $user): array
    {
        $row = $this->baseConstanciaQuery($user)
            ->select([
                'c.id_respuesta',
                'c.nombre_curso',
                'c.correo_electronico',
                DB::raw("
                    NULLIF(
                        TRIM(CONCAT_WS(' ',
                            NULLIF(TRIM(cap.nombre), ''),
                            NULLIF(TRIM(cap.apellido_paterno), ''),
                            NULLIF(TRIM(cap.apellido_materno), '')
                        )),
                        ''
                    ) AS nombre_persona
                "),
            ])
            ->where('c.id_respuesta', $idRespuesta)
            ->first();

        return [
            'folio'              => (string) ($row->id_respuesta ?? $idRespuesta),
            'nombre_curso'       => (string) ($row->nombre_curso ?? ''),
            'correo_electronico' => (string) ($row->correo_electronico ?? ''),
            'nombre_persona'     => (string) ($row->nombre_persona ?? ''),
        ];
    }

    private function baseConstanciaOnlyQuery($user): Builder
    {
        $q = DB::table('public.tbl_constancias as c');

        ConstanciaVisibilityByName::apply($q, $user, 'c');
        $this->applyValidConstanciaFilters($q);

        return $q;
    }

    private function baseConstanciaQuery($user): Builder
    {
        $capLast = DB::raw("
            (
                SELECT DISTINCT ON (UPPER(TRIM(curp)))
                    curp,
                    nombre,
                    apellido_paterno,
                    apellido_materno
                FROM public.a2_acciones_capacitacion
                WHERE curp IS NOT NULL
                  AND TRIM(curp) <> ''
                ORDER BY UPPER(TRIM(curp)), id_cat DESC NULLS LAST
            ) as cap
        ");

        $q = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                $capLast,
                DB::raw("UPPER(TRIM(cap.curp))"),
                '=',
                DB::raw("UPPER(TRIM(c.curp))")
            );

        ConstanciaVisibilityByName::apply($q, $user, 'c');
        $this->applyValidConstanciaFilters($q);

        return $q;
    }

    private function applyValidConstanciaFilters(Builder $query): void
    {
        $query->whereNotNull('c.id_puesto')
            ->whereRaw("BTRIM(COALESCE(c.id_puesto, '')) <> ''")
            ->whereNotNull('c.estatus');
    }

    private function statusLabel(int $estatus): string
    {
        return match ($estatus) {
            self::CONST_PENDIENTE => 'PENDIENTE',
            self::CONST_CONCLUIDO => 'ACEPTADO',
            self::CONST_RECHAZADO => 'RECHAZADO',
            default => 'SIN ESTATUS',
        };
    }

    private function sendConstanciaDecisionEmail(
        string $correoDestinatario,
        string $nombrePersona,
        string $nombreCurso,
        string $folio,
        string $fechaHora,
        string $tipo,
        ?string $motivo = null,
        string $curp = ''
    ): bool {
        $result = app(ConstanciaNotificationService::class)->sendDecisionEmail(
            correoDestinatario: $correoDestinatario,
            nombrePersona: $nombrePersona,
            nombreCurso: $nombreCurso,
            folio: $folio,
            fechaHora: $fechaHora,
            tipo: $tipo,
            motivo: $motivo,
            curp: $curp,
            historialCapacitacion: $this->historialCapacitacionPorCurp($curp),
            actorUserId: auth()->id() ? (int) auth()->id() : null
        );

        return (bool) ($result['sent'] ?? false);
    }

    private function parseMailList(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $emails = preg_split('/[;,]+/', $raw) ?: [];

        return collect($emails)
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveIdInstanciaSafe(string $instTxt): ?string
    {
        $instTxt = trim($instTxt);
        if ($instTxt === '') {
            return null;
        }

        $tables = DB::table('information_schema.tables')
            ->where('table_schema', 'public')
            ->where(function ($q) {
                $q->whereIn('table_name', ['cat_instancias', 'cat_instancia'])
                    ->orWhereRaw("table_name ILIKE ?", ['%instanc%']);
            })
            ->orderBy('table_name')
            ->pluck('table_name')
            ->unique()
            ->values()
            ->all();

        foreach ($tables as $t) {
            $cols = $this->columnsFor('public', $t);
            $set  = array_flip($cols);

            $idCol  = $this->firstExistingColumnFromSet($set, ['id_instancia', 'id']);
            $txtCol = $this->firstExistingColumnFromSet($set, ['descripcion', 'instancia', 'nombre_instancia', 'nombre']);

            if (! $idCol || ! $txtCol) {
                continue;
            }

            $existsById = DB::table("public.$t")->where($idCol, $instTxt)->exists();
            if ($existsById) {
                return (string) $instTxt;
            }

            $id = DB::table("public.$t")
                ->whereRaw("TRIM(UPPER($txtCol)) = TRIM(UPPER(?))", [$instTxt])
                ->value($idCol);

            if (! empty($id)) {
                return (string) $id;
            }
        }

        return null;
    }

    private function resolveTematicaFromCursoSafe(int $idAccion): ?string
    {
        $cols = $this->columnsFor('public', 'a1_cat_acciones');
        if (! in_array('tematica', $cols, true)) {
            return null;
        }

        $t = DB::table('public.a1_cat_acciones')
            ->where('id_accion', $idAccion)
            ->value('tematica');

        $t = trim((string) $t);

        return $t !== '' ? $t : null;
    }

    private function resolveIdTematicaSafe(string $tematicaTxt): ?string
    {
        $tematicaTxt = trim($tematicaTxt);
        if ($tematicaTxt === '') {
            return null;
        }

        $cols = $this->columnsFor('public', 'cat_tematica');
        if (! in_array('id_tematica', $cols, true) || ! in_array('tematica', $cols, true)) {
            return null;
        }

        $id = DB::table('public.cat_tematica')
            ->whereRaw("TRIM(UPPER(tematica)) = TRIM(UPPER(?))", [$tematicaTxt])
            ->value('id_tematica');

        return ! empty($id) ? (string) $id : null;
    }

    private function resolveIdCatEstatusVigente(): int
    {
        try {
            $id = DB::table('public.cat_estatus')
                ->whereRaw("TRIM(UPPER(descripcion)) = 'VIGENTE'")
                ->value('id_cat_estatus');

            if (! empty($id)) {
                return (int) $id;
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo resolver id_cat_estatus VIGENTE.', [
                'message' => $e->getMessage(),
            ]);
        }

        return self::PLANTILLA_ID_CAT_ESTATUS_DEFAULT;
    }

    private function resolveIdCatEstatusNoVigente(): int
    {
        try {
            $id = DB::table('public.cat_estatus')
                ->whereRaw("TRIM(UPPER(descripcion)) = 'NO VIGENTE'")
                ->value('id_cat_estatus');

            if (! empty($id)) {
                return (int) $id;
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo resolver id_cat_estatus NO VIGENTE.', [
                'message' => $e->getMessage(),
            ]);
        }

        return self::PLANTILLA_ID_CAT_ESTATUS_NO_VIGENTE_DEFAULT;
    }

    private function historialCapacitacionPorCurp(string $curp): array
    {
        $curp = trim($curp);

        if ($curp === '') {
            return [];
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Historial de capacitación para correo
            |--------------------------------------------------------------------------
            | Debe mostrar:
            | - Cursos concluidos vigentes.
            | - Cursos pendientes vigentes.
            | - Cursos en ALTA.
            |
            | Debe ocultar:
            | - Cursos cuyo catálogo esté NO VIGENTE.
            | - Cursos del empleado con estatus BAJA o NO VIGENTE.
            |
            | Reglas:
            | - public.a1_cat_acciones.estatus debe ser VIGENTE.
            | - public.a2_acciones_empleados.id_cat_estatus puede ser:
            |       NULL = pendiente sin atender
            |       1    = VIGENTE
            |       2    = ALTA
            */
            return DB::table('public.a2_acciones_empleados as a')
                ->join('public.a1_cat_acciones as b', 'a.id_accion', '=', 'b.id_accion')
                ->leftJoin('public.cat_estatus as c', 'a.id_cat_estatus', '=', 'c.id_cat_estatus')
                ->whereRaw('TRIM(UPPER(a.curp)) = TRIM(UPPER(?))', [$curp])

                // El curso/acción del catálogo debe estar vigente.
                ->whereRaw("TRIM(UPPER(COALESCE(b.estatus, ''))) = 'VIGENTE'")

                // El registro del empleado debe estar vigente, en alta o pendiente sin estatus.
                ->where(function ($q) {
                    $q->whereNull('a.id_cat_estatus')
                        ->orWhereIn('a.id_cat_estatus', [1, 2]);
                })

                ->select([
                    'a.id_accion',
                    'b.nombre_accion',
                    'b.duracion_hrs',
                    'a.horas_real',
                    'a.fecha_ini',
                    'a.fecha_fin',
                    'a.calificacion',
                    DB::raw("COALESCE(c.descripcion, 'SIN ESTATUS') AS descripcion_estatus"),
                    DB::raw("COALESCE(a.observaciones, '') AS observaciones"),
                    DB::raw("
                        CASE
                            WHEN a.fecha_fin IS NOT NULL THEN 'CONCLUIDO'
                            ELSE 'PENDIENTE'
                        END AS estatus_accion
                    "),
                ])
                ->orderByRaw("CASE WHEN a.fecha_fin IS NULL THEN 1 ELSE 0 END")
                ->orderByDesc('a.fecha_fin')
                ->orderByDesc('a.fecha_ini')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

        } catch (\Throwable $e) {
            Log::warning('No se pudo obtener historial de capacitación para correo de constancias.', [
                'curp'    => $curp,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function resolveHorasProgramadasSafe(
        int $idAccion,
        $horasCatalogo = null,
        $horasConstancia = null
    ): ?float {
        /*
        |--------------------------------------------------------------------------
        | 1) Primero intenta usar duracion_hrs del catálogo a1_cat_acciones.
        |--------------------------------------------------------------------------
        */
        $horas = $this->parseHorasToFloat($horasCatalogo);

        if ($horas !== null && $horas > 0) {
            return $horas;
        }

        /*
        |--------------------------------------------------------------------------
        | 2) Si catálogo no trae horas, busca horas_progamadas existentes.
        |--------------------------------------------------------------------------
        */
        try {
            $horasExistentes = DB::table('public.a2_acciones_empleados')
                ->select('horas_progamadas', DB::raw('COUNT(*) as total'))
                ->where('id_accion', $idAccion)
                ->whereNotNull('horas_progamadas')
                ->where('horas_progamadas', '>', 0)
                ->groupBy('horas_progamadas')
                ->orderByDesc('total')
                ->value('horas_progamadas');

            $horas = $this->parseHorasToFloat($horasExistentes);

            if ($horas !== null && $horas > 0) {
                return $horas;
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudieron resolver horas_progamadas desde acciones existentes.', [
                'message'   => $e->getMessage(),
                'id_accion' => $idAccion,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3) Si tampoco hay horas_progamadas, busca horas_real existentes.
        |--------------------------------------------------------------------------
        */
        try {
            $horasRealesExistentes = DB::table('public.a2_acciones_empleados')
                ->select('horas_real', DB::raw('COUNT(*) as total'))
                ->where('id_accion', $idAccion)
                ->whereNotNull('horas_real')
                ->where('horas_real', '>', 0)
                ->groupBy('horas_real')
                ->orderByDesc('total')
                ->value('horas_real');

            $horas = $this->parseHorasToFloat($horasRealesExistentes);

            if ($horas !== null && $horas > 0) {
                return $horas;
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudieron resolver horas_real desde acciones existentes.', [
                'message'   => $e->getMessage(),
                'id_accion' => $idAccion,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4) Último recurso: horas_realizadas de la constancia.
        |--------------------------------------------------------------------------
        */
        $horas = $this->parseHorasToFloat($horasConstancia);

        if ($horas !== null && $horas > 0) {
            return $horas;
        }

        return null;
    }

    private function parseHorasToFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $raw = str_replace(',', '.', $raw);

        if (! is_numeric($raw)) {
            return null;
        }

        return round((float) $raw, 2);
    }

    private function resolveHorasRealSafe($horasCatalogo = null, $horasConstancia = null): ?float
    {
        $catalogo = $this->parseHorasToFloat($horasCatalogo);
        $constancia = $this->parseHorasToFloat($horasConstancia);

        /*
        |--------------------------------------------------------------------------
        | Regla para horas_real
        |--------------------------------------------------------------------------
        | Si existe duración válida en catálogo:
        | - Constancia vacía, nula, inválida o en cero: usa catálogo.
        | - Constancia mayor al catálogo: usa catálogo.
        | - Constancia menor o igual al catálogo: usa constancia.
        |
        | Si el catálogo no trae duración válida:
        | - Usa constancia solo si viene válida y mayor a cero.
        */
        if ($catalogo !== null && $catalogo > 0) {
            if ($constancia === null || $constancia <= 0) {
                return $catalogo;
            }

            if ($constancia > $catalogo) {
                return $catalogo;
            }

            return $constancia;
        }

        if ($constancia !== null && $constancia > 0) {
            return $constancia;
        }

        return null;
    }

    private function resolveObservacionesBase(int $idAccion): string
    {
        return match ($idAccion) {
            1, 2 => 'PAC 2025',
            self::CURSO_OBLIGATORIO_BASE_ID, 1000002, self::CURSO_CIBERSEGURIDAD_ID => 'OBLIGATORIO',
            default => 'CURSO EXTRA',
        };
    }

    /**
     * Validación especial 1000001 / 1000011.
     *
     * Tablas y relación:
     * - public.tbl_constancias: origen de la constancia aceptada; se identifica
     *   por id_respuesta y trae curp, id_puesto y nombre_curso.
     * - public.a1_cat_acciones: convierte nombre_curso en id_accion real.
     * - public.a2_acciones_empleados: guarda los cursos del empleado; se relaciona
     *   por id_puesto + curp + id_accion.
     * - public.cat_estatus: provee id_cat_estatus = NO VIGENTE.
     *
     * Regla:
     * Al aceptar 1000011, si el mismo empleado tiene 1000001 activo pero sin
     * fecha_fin, se marca 1000001 como NO VIGENTE. Si 1000001 ya tiene fecha_fin,
     * se considera concluido y no se modifica.
     */
    private function anularCursoBasePendientePorCiberseguridad(
        string $idPuestoTxt,
        string $curp,
        int $idCatEstatusNoVigente
    ): ?array {
        $cursosBase = DB::table('public.a2_acciones_empleados')
            ->select([
                'id_empl_accion',
                'id_puesto',
                'curp',
                'id_accion',
                'id_cat_estatus',
                'fecha_fin',
                'observaciones',
            ])
            ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
            ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
            ->where('id_accion', self::CURSO_OBLIGATORIO_BASE_ID)
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                    ->orWhereRaw("BTRIM(COALESCE(fecha_fin::text, '')) = ''");
            })
            ->where(function ($q) {
                $q->whereNull('id_cat_estatus')
                    ->orWhereIn('id_cat_estatus', [
                        self::PLANTILLA_ID_CAT_ESTATUS_DEFAULT,
                        2,
                    ]);
            })
            ->lockForUpdate()
            ->get();

        if ($cursosBase->isEmpty()) {
            return null;
        }

        $ids = $cursosBase
            ->pluck('id_empl_accion')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($ids)) {
            return null;
        }

        DB::table('public.a2_acciones_empleados')
            ->whereIn('id_empl_accion', $ids)
            ->update([
                'id_cat_estatus' => $idCatEstatusNoVigente,
                'observaciones' => self::OBSERVACION_ANULACION_CURSO_BASE,
            ]);

        $primerCursoBase = $cursosBase->first();

        return [
            'id_empl_accion' => (string) ($primerCursoBase->id_empl_accion ?? ''),
            'ids_empl_accion' => array_map(fn ($id) => (string) $id, $ids),
            'total_anulados' => count($ids),
            'id_puesto' => (string) ($primerCursoBase->id_puesto ?? ''),
            'curp' => (string) ($primerCursoBase->curp ?? ''),
            'estatus_nuevo' => $idCatEstatusNoVigente,
            'registros_anteriores' => $cursosBase
                ->map(fn ($row) => [
                    'id_empl_accion' => (string) ($row->id_empl_accion ?? ''),
                    'id_cat_estatus' => $row->id_cat_estatus ?? null,
                    'fecha_fin' => $row->fecha_fin ?? null,
                    'observaciones' => $row->observaciones ?? null,
                ])
                ->values()
                ->all(),
            'observaciones_nuevas' => self::OBSERVACION_ANULACION_CURSO_BASE,
        ];
    }

    private function actionAlreadyConcludedForEmployee(string $idPuestoTxt, string $curp, int $idAccion): bool
    {
        return DB::table('public.a2_acciones_empleados')
            ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
            ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
            ->where('id_accion', $idAccion)
            ->whereNotNull('fecha_fin')
            ->exists();
    }

    private function rejectDuplicatedConstanciaInsideTransaction(
        string $idRespuesta,
        $user,
        string $curp
    ): array {
        $motivoDuplicado = 'La constancia ya había sido generada y enviada con anterioridad. Para evitar duplicidades en el proceso, este registro fue marcado como rechazado.';

        $colsConstancias = $this->columnsFor('public', 'tbl_constancias');
        $colSetConst = array_flip($colsConstancias);

        $motivoColumn = $this->firstExistingColumnFromSet($colSetConst, [
            'motivo_rechazo',
            'motivo',
            'observaciones',
            'comentarios',
        ]);

        $fechaRechazoColumn = $this->firstExistingColumnFromSet($colSetConst, [
            'fecha_rechazo',
            'fecha_rechazo_at',
        ]);

        $updateData = [
            'estatus'             => self::CONST_RECHAZADO,
            'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_TIMESTAMP)"),
            'fecha_ultima_accion' => DB::raw("CURRENT_TIMESTAMP"),
        ];

        if ($motivoColumn) {
            $updateData[$motivoColumn] = $motivoDuplicado;
        }

        if ($fechaRechazoColumn) {
            $updateData[$fechaRechazoColumn] = DB::raw("CURRENT_TIMESTAMP");
        }

        $updated = DB::table('public.tbl_constancias')
            ->where('id_respuesta', $idRespuesta)
            ->where('estatus', self::CONST_PENDIENTE)
            ->update($updateData);

        if ($updated < 1) {
            return [
                'status'  => false,
                'code'    => 409,
                'message' => 'La constancia cambió de estatus mientras se procesaba. Intenta recargar la tabla.',
            ];
        }

        $notify = $this->notificationDataForConstancia($idRespuesta, $user);

        return [
            'status'             => true,
            'duplicate_rejected' => true,
            'curp'               => $curp,
            'correo_electronico' => (string) ($notify['correo_electronico'] ?? ''),
            'nombre_persona'     => (string) ($notify['nombre_persona'] ?? ''),
            'nombre_curso'       => (string) ($notify['nombre_curso'] ?? ''),
            'folio'              => (string) ($notify['folio'] ?? $idRespuesta),
            'motivo'             => $motivoDuplicado,
        ];
    }

    private function columnsFor(string $schema, string $table): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->orderBy('ordinal_position')
            ->pluck('column_name')
            ->map(fn ($c) => (string) $c)
            ->all();
    }

    private function firstExistingColumnFromSet(array $set, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (isset($set[$c])) {
                return $c;
            }
        }

        return null;
    }
}
