<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Mail\ConstanciaDecisionMail;
use App\Support\ConstanciaVisibilityByName;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    /**
     * Cursos que deben guardarse con observaciones PAC 2025.
     */
    private const PAC_2025_COURSES = [
        'INTRODUCCIÓN A LA ADMINISTRACIÓN PÚBLICA FEDERAL',
        'ÉTICA E INTEGRIDAD PÚBLICA PARA UN BUEN GOBIERNO',
    ];

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
                'id_respuesta' => 'required',
                'accion'       => 'required|string',
                'motivo'       => 'nullable|string|max:2000',
            ]);

            $idRespuesta = trim((string) $request->input('id_respuesta'));
            $accion      = strtoupper(trim((string) $request->input('accion')));
            $motivo      = trim((string) $request->input('motivo', ''));

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

            return $this->acceptConstancia($idRespuesta, $user);

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
            motivo: $motivo
        );

        return response()->json([
            'status'  => true,
            'message' => $emailEnviado
                ? 'Constancia rechazada y correo enviado correctamente.'
                : 'Constancia rechazada correctamente. No fue posible enviar el correo.',
        ], 200);
    }

    private function acceptConstancia(string $idRespuesta, $user)
    {
        $resultado = DB::transaction(function () use ($idRespuesta, $user) {
            $colsEmp = $this->columnsFor('public', 'a2_acciones_empleados');
            $colSet  = array_flip($colsEmp);

            $hasCalificacion = isset($colSet['calificacion']);
            $hasHorasProg    = isset($colSet['horas_progamadas']);
            $hasEvalApr      = isset($colSet['eval_aprendizaje']);

            $idCatEstatusVigente = $this->resolveIdCatEstatusVigente();

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
                $m = (int) date('n', strtotime((string) $c->fecha_inicio));
                $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
            }

            if (empty($c->fecha_inicio) || empty($c->fecha_final) || empty($idTrimestre)) {
                return [
                    'status'  => false,
                    'code'    => 422,
                    'message' => 'La constancia no trae fechas suficientes (fecha_inicio/fecha_final).',
                ];
            }

            $horasReal = $this->parseHorasToFloat($c->horas_realizadas ?? null);

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

            DB::select("SELECT pg_advisory_xact_lock(5001)");
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$curp]);

            $existe = DB::table('public.a2_acciones_empleados')
                ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                ->where('id_accion', $idAccion)
                ->exists();

            /*
            |--------------------------------------------------------------------------
            | Observaciones
            |--------------------------------------------------------------------------
            | Cursos especiales:
            | - INTRODUCCIÓN A LA ADMINISTRACIÓN PÚBLICA FEDERAL
            | - ÉTICA E INTEGRIDAD PÚBLICA PARA UN BUEN GOBIERNO
            |
            | Para esos cursos se guarda PAC 2025.
            */
            $obsBase = $this->resolveObservacionesBase(
                cursoTxt: $cursoTxt,
                calTexto: $calTexto,
                appendCalificacion: ! $hasCalificacion
            );

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
                | Si es curso PAC 2025, se fuerza PAC 2025.
                | Si no es curso PAC 2025, solo se llena si observaciones está vacío.
                */
                if ($this->isPac2025Course($cursoTxt)) {
                    $upd['observaciones'] = $obsBase;
                } else {
                    $upd['observaciones'] = DB::raw(
                        "COALESCE(NULLIF(BTRIM(observaciones), ''), " . $this->quoteSqlString($obsBase) . ")"
                    );
                }

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

            $updated = DB::table('public.tbl_constancias')
                ->where('id_respuesta', $idRespuesta)
                ->where('estatus', self::CONST_PENDIENTE)
                ->update([
                    'estatus'             => self::CONST_CONCLUIDO,
                    'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_TIMESTAMP)"),
                    'fecha_ultima_accion' => DB::raw("CURRENT_TIMESTAMP"),
                ]);

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
                'message'            => 'Constancia aceptada y procesada correctamente.',
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
            tipo: 'aceptacion',
            motivo: null
        );

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
        ?string $motivo = null
    ): bool {
        $correoDestinatario = trim($correoDestinatario);
        $nombrePersona = trim($nombrePersona) !== '' ? trim($nombrePersona) : 'Usuario';
        $nombreCurso = trim($nombreCurso) !== '' ? trim($nombreCurso) : 'No especificado';
        $folio = trim($folio) !== '' ? trim($folio) : 'S/F';
        $tipo = strtolower(trim($tipo));

        if ($correoDestinatario === '' || ! filter_var($correoDestinatario, FILTER_VALIDATE_EMAIL)) {
            Log::warning('No se encontró correo válido para notificación de constancia.', [
                'correo' => $correoDestinatario,
                'folio'  => $folio,
                'tipo'   => $tipo,
            ]);

            return false;
        }

        try {
            $decision = $tipo === 'rechazo'
                ? 'RECHAZADO'
                : 'ACEPTADO';

            $ccRaw = $tipo === 'rechazo'
                ? trim((string) env('CONSTANCIAS_RECHAZO_CC', ''))
                : trim((string) env('CONSTANCIAS_ACEPTACION_CC', ''));

            $ccList = $this->parseMailList($ccRaw);

            $mail = Mail::to($correoDestinatario);

            if (! empty($ccList)) {
                $mail->cc($ccList);
            }

            $mail->send(new ConstanciaDecisionMail(
                nombrePersona: $nombrePersona,
                nombreCurso: $nombreCurso,
                folio: $folio,
                fechaHora: $fechaHora,
                decision: $decision,
                motivo: $motivo
            ));

            return true;

        } catch (\Throwable $mailEx) {
            Log::error('Error enviando correo de constancia: ' . $mailEx->getMessage(), [
                'correo' => $correoDestinatario,
                'folio'  => $folio,
                'tipo'   => $tipo,
                'trace'  => $mailEx->getTraceAsString(),
            ]);

            return false;
        }
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

    private function resolveObservacionesBase(
        string $cursoTxt,
        string $calTexto,
        bool $appendCalificacion = false
    ): string {
        if ($this->isPac2025Course($cursoTxt)) {
            return 'PAC 2025';
        }

        $obs = 'Curso Extra.';

        if ($appendCalificacion) {
            $obs .= ' Calificación: ' . $calTexto . '.';
        }

        return $obs;
    }

    private function isPac2025Course(string $cursoTxt): bool
    {
        $curso = $this->normalizeText($cursoTxt);

        foreach (self::PAC_2025_COURSES as $course) {
            if ($curso === $this->normalizeText($course)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtoupper($value, 'UTF-8');
    }

    private function quoteSqlString(string $value): string
    {
        try {
            return DB::connection()->getPdo()->quote($value);
        } catch (\Throwable $e) {
            return "'" . str_replace("'", "''", $value) . "'";
        }
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