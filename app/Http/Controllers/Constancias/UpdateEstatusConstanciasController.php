<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
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

    private const PLANTILLA_ID_CAT_ESTATUS_DEFAULT = 1;

    public function update(Request $request)
    {
        try {
            $request->validate([
                'id_respuesta' => 'required',
                'accion'       => 'required|string',
                'motivo'       => 'nullable|string|max:2000',
            ]);

            $idRespuesta = trim((string) $request->id_respuesta);
            $accion      = strtoupper(trim((string) $request->accion));
            $motivo      = trim((string) $request->input('motivo', ''));

            if (!in_array($accion, ['ACEPTAR', 'RECHAZAR'], true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Acción inválida.',
                ], 200);
            }

            if ($accion === 'RECHAZAR' && $motivo === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'Debes capturar el motivo del rechazo.',
                ], 200);
            }

            if ($accion === 'RECHAZAR') {
                return $this->rejectConstancia($idRespuesta, $motivo);
            }

            return $this->acceptConstancia($idRespuesta);

        } catch (\Throwable $th) {
            Log::error('Constancias update error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al procesar el registro.',
            ], 200);
        }
    }

    private function rejectConstancia(string $idRespuesta, string $motivo)
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

        $capLast = DB::raw("
            (
                SELECT DISTINCT ON (UPPER(TRIM(curp)))
                    curp,
                    nombre,
                    apellido_paterno,
                    apellido_materno
                FROM public.a2_acciones_capacitacion
                WHERE curp IS NOT NULL AND TRIM(curp) <> ''
                ORDER BY UPPER(TRIM(curp)), id_cat DESC NULLS LAST
            ) as cap
        ");

        $registro = DB::table('public.tbl_constancias as c')
            ->leftJoin(
                $capLast,
                DB::raw("UPPER(TRIM(cap.curp))"),
                '=',
                DB::raw("UPPER(TRIM(c.curp))")
            )
            ->select([
                'c.id_respuesta',
                'c.curp',
                'c.nombre_curso',
                'c.correo_electronico',
                'c.fecha_ultima_accion',
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

        if (!$registro) {
            return response()->json([
                'status' => false,
                'message' => 'Registro no encontrado.',
            ], 200);
        }

        $ahora = Carbon::now('America/Mexico_City');

        DB::transaction(function () use ($idRespuesta, $motivo, $motivoColumn, $fechaRechazoColumn) {
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

            DB::table('public.tbl_constancias')
                ->where('id_respuesta', $idRespuesta)
                ->update($updateData);
        });

        $emailEnviado = $this->sendConstanciaDecisionEmail(
            correoDestinatario: (string) ($registro->correo_electronico ?? ''),
            nombrePersona: (string) ($registro->nombre_persona ?? ''),
            nombreCurso: (string) ($registro->nombre_curso ?? ''),
            folio: (string) $registro->id_respuesta,
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

    private function acceptConstancia(string $idRespuesta)
    {
        $resultado = DB::transaction(function () use ($idRespuesta) {
            $colsEmp = $this->columnsFor('public', 'a2_acciones_empleados');
            $colSet  = array_flip($colsEmp);

            $hasCalificacion = isset($colSet['calificacion']);
            $hasHorasProg    = isset($colSet['horas_progamadas']);
            $hasEvalApr      = isset($colSet['eval_aprendizaje']);

            $capLast = DB::raw("
                (
                    SELECT DISTINCT ON (UPPER(TRIM(curp)))
                        curp,
                        nombre,
                        apellido_paterno,
                        apellido_materno
                    FROM public.a2_acciones_capacitacion
                    WHERE curp IS NOT NULL AND TRIM(curp) <> ''
                    ORDER BY UPPER(TRIM(curp)), id_cat DESC NULLS LAST
                ) as cap
            ");

            $c = DB::table('public.tbl_constancias as c')
                ->leftJoin(
                    $capLast,
                    DB::raw("UPPER(TRIM(cap.curp))"),
                    '=',
                    DB::raw("UPPER(TRIM(c.curp))")
                )
                ->select([
                    'c.id_respuesta',
                    'c.curp',
                    'c.nombre_curso',
                    'c.id_puesto',
                    'c.fecha_inicio',
                    'c.fecha_final',
                    'c.horas_realizadas',
                    'c.calificacion',
                    'c.instancia',
                    'c.instancia_otro',
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

            if (!$c) {
                return [
                    'status' => false,
                    'message' => 'Registro de constancia no encontrado.',
                ];
            }

            $curp        = trim((string) ($c->curp ?? ''));
            $cursoTxt    = trim((string) ($c->nombre_curso ?? ''));
            $idPuestoTxt = trim((string) ($c->id_puesto ?? ''));

            if ($curp === '' || $cursoTxt === '' || $idPuestoTxt === '') {
                return [
                    'status'  => false,
                    'message' => 'Faltan datos en la constancia (CURP, curso o id_puesto).',
                ];
            }

            $accionCat = DB::table('public.a1_cat_acciones')
                ->select(['id_accion', 'duracion_hrs'])
                ->whereRaw('TRIM(UPPER(nombre_accion)) = TRIM(UPPER(?))', [$cursoTxt])
                ->first();

            if (!$accionCat) {
                return [
                    'status'  => false,
                    'message' => 'El curso NO existe en el catálogo (a1_cat_acciones). No se puede procesar.',
                ];
            }

            $idAccion = (int) $accionCat->id_accion;
            $horasProgramadas = $accionCat->duracion_hrs ?? null;

            $instTxt = trim((string) (($c->instancia ?? '') !== '' ? $c->instancia : ($c->instancia_otro ?? '')));
            $idInstancia = $this->resolveIdInstanciaSafe($instTxt);

            if (!$idInstancia) {
                return [
                    'status'  => false,
                    'message' => "No se pudo mapear la instancia '{$instTxt}' a un id_instancia del catálogo.",
                ];
            }

            $tematicaTxt = $this->resolveTematicaFromCursoSafe($idAccion);
            if (!$tematicaTxt) {
                return [
                    'status'  => false,
                    'message' => 'No se pudo obtener la temática asociada al curso desde el catálogo.',
                ];
            }

            $idTematica = $this->resolveIdTematicaSafe($tematicaTxt);
            if (!$idTematica) {
                return [
                    'status'  => false,
                    'message' => "No se pudo mapear la temática '{$tematicaTxt}' a cat_tematica.",
                ];
            }

            $idTrimestre = null;
            if (!empty($c->fecha_inicio)) {
                $m = (int) date('n', strtotime($c->fecha_inicio));
                $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
            }

            if (empty($c->fecha_inicio) || empty($c->fecha_final) || empty($idTrimestre)) {
                return [
                    'status'  => false,
                    'message' => 'La constancia no trae fechas suficientes (fecha_inicio/fecha_final).',
                ];
            }

            $horasReal = null;
            $hrsRaw = trim((string) ($c->horas_realizadas ?? ''));
            if ($hrsRaw !== '') {
                $hrsRaw = str_replace(',', '.', $hrsRaw);
                $horasReal = (float) $hrsRaw;
            }

            $cal = 100;
            $calRaw = trim((string) ($c->calificacion ?? ''));
            if ($calRaw !== '' && is_numeric($calRaw)) {
                $cal = (int) $calRaw;
            }
            if ($cal < 70) $cal = 70;
            if ($cal > 100) $cal = 100;

            $existe = DB::table('public.a2_acciones_empleados')
                ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                ->where('id_accion', $idAccion)
                ->exists();

            DB::select("SELECT pg_advisory_xact_lock(5001)");
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$curp]);

            $obsBase = 'Curso Extra.';
            if (!$hasCalificacion) {
                $obsBase .= ' Calificación: '.$cal.'.';
            }

            if ($existe) {
                $upd = [
                    'id_finalidad'     => 6,
                    'id_cat_estatus'   => self::PLANTILLA_ID_CAT_ESTATUS_DEFAULT,
                    'fecha_ini'        => $c->fecha_inicio,
                    'fecha_fin'        => $c->fecha_final,
                    'id_trimestre'     => $idTrimestre,
                    'id_instancia'     => (string) $idInstancia,
                    'id_cat_tematica'  => (string) $idTematica,
                    'horas_real'       => $horasReal,
                    'observaciones'    => DB::raw("COALESCE(NULLIF(BTRIM(observaciones), ''), '{$obsBase}')"),
                ];

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
                    'id_cat_estatus'   => self::PLANTILLA_ID_CAT_ESTATUS_DEFAULT,
                    'id_cat_tematica'  => (string) $idTematica,
                ];

                if ($hasEvalApr) {
                    $ins['eval_aprendizaje'] = null;
                }
                if ($hasHorasProg) {
                    $ins['horas_progamadas'] = $horasProgramadas;
                }
                if ($hasCalificacion) {
                    $ins['calificacion'] = $cal;
                }

                DB::table('public.a2_acciones_empleados')->insert($ins);
            }

            DB::table('public.tbl_constancias')
                ->where('id_respuesta', $idRespuesta)
                ->update([
                    'estatus'             => self::CONST_CONCLUIDO,
                    'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_TIMESTAMP)"),
                    'fecha_ultima_accion' => DB::raw("CURRENT_TIMESTAMP"),
                ]);

            return [
                'status' => true,
                'message' => 'Constancia aceptada y procesada correctamente.',
                'correo_electronico' => (string) ($c->correo_electronico ?? ''),
                'nombre_persona' => (string) ($c->nombre_persona ?? ''),
                'nombre_curso' => (string) ($c->nombre_curso ?? ''),
                'folio' => (string) ($c->id_respuesta ?? ''),
            ];
        });

        if (!$resultado['status']) {
            return response()->json([
                'status'  => false,
                'message' => $resultado['message'],
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
            motivo: null
        );

        return response()->json([
            'status'  => true,
            'message' => $emailEnviado
                ? 'Constancia aceptada, procesada y correo enviado correctamente.'
                : 'Constancia aceptada y procesada correctamente. No fue posible enviar el correo.',
        ], 200);
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

        if ($correoDestinatario === '' || !filter_var($correoDestinatario, FILTER_VALIDATE_EMAIL)) {
            Log::warning('No se encontró correo válido para notificación de constancia.', [
                'correo' => $correoDestinatario,
                'folio' => $folio,
                'tipo' => $tipo,
            ]);
            return false;
        }

        try {
            if ($tipo === 'rechazo') {
                $subject = 'Constancia rechazada - '.$nombreCurso.' - Folio '.$folio;

                $html = '
                    <div style="font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#222;">
                        <p>Hola <strong>'.e($nombrePersona).'</strong>,</p>

                        <p>Te informamos que tu trámite/constancia fue <strong>rechazado</strong>.</p>

                        <p><strong>Detalle del rechazo:</strong></p>
                        <ul>
                            <li><strong>Folio / ID:</strong> '.e($folio).'</li>
                            <li><strong>Nombre del empleado:</strong> '.e($nombrePersona).'</li>
                            <li><strong>Nombre del curso:</strong> '.e($nombreCurso).'</li>
                            <li><strong>Fecha y hora del rechazo:</strong> '.e($fechaHora).'</li>
                            <li><strong>Motivo:</strong> '.nl2br(e((string) $motivo)).'</li>
                        </ul>

                        <p>Por favor revisa la información y realiza las correcciones necesarias.</p>

                        <p>Saludos.</p>
                    </div>
                ';

                $cc = trim((string) env('CONSTANCIAS_RECHAZO_CC', ''));
            } else {
                $subject = 'Constancia aceptada - '.$nombreCurso.' - Folio '.$folio;

                $html = '
                    <div style="font-family: Arial, Helvetica, sans-serif; font-size:14px; color:#222;">
                        <p>Hola <strong>'.e($nombrePersona).'</strong>,</p>

                        <p>Te informamos que tu constancia fue <strong>aceptada</strong> correctamente.</p>

                        <p><strong>Detalle de la aceptación:</strong></p>
                        <ul>
                            <li><strong>Folio / ID:</strong> '.e($folio).'</li>
                            <li><strong>Nombre del empleado:</strong> '.e($nombrePersona).'</li>
                            <li><strong>Nombre del curso:</strong> '.e($nombreCurso).'</li>
                            <li><strong>Fecha y hora de la aceptación:</strong> '.e($fechaHora).'</li>
                        </ul>

                        <p>Tu registro fue validado correctamente.</p>

                        <p>Saludos.</p>
                    </div>
                ';

                $cc = trim((string) env('CONSTANCIAS_ACEPTACION_CC', ''));
            }

            Mail::send([], [], function ($message) use ($correoDestinatario, $subject, $html, $cc) {
                $message->to($correoDestinatario)
                    ->subject($subject)
                    ->html($html);

                if ($cc !== '' && filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $message->cc($cc);
                }
            });

            return true;
        } catch (\Throwable $mailEx) {
            Log::error('Error enviando correo de constancia: '.$mailEx->getMessage(), [
                'correo' => $correoDestinatario,
                'folio' => $folio,
                'tipo' => $tipo,
            ]);
            return false;
        }
    }

    private function resolveIdInstanciaSafe(string $instTxt): ?string
    {
        $instTxt = trim($instTxt);
        if ($instTxt === '') return null;

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

            if (!$idCol || !$txtCol) continue;

            $existsById = DB::table("public.$t")->where($idCol, $instTxt)->exists();
            if ($existsById) return (string) $instTxt;

            $id = DB::table("public.$t")
                ->whereRaw("TRIM(UPPER($txtCol)) = TRIM(UPPER(?))", [$instTxt])
                ->value($idCol);

            if (!empty($id)) return (string) $id;
        }

        return null;
    }

    private function resolveTematicaFromCursoSafe(int $idAccion): ?string
    {
        $cols = $this->columnsFor('public', 'a1_cat_acciones');
        if (!in_array('tematica', $cols, true)) return null;

        $t = DB::table('public.a1_cat_acciones')
            ->where('id_accion', $idAccion)
            ->value('tematica');

        $t = trim((string) $t);
        return $t !== '' ? $t : null;
    }

    private function resolveIdTematicaSafe(string $tematicaTxt): ?string
    {
        $tematicaTxt = trim($tematicaTxt);
        if ($tematicaTxt === '') return null;

        $cols = $this->columnsFor('public', 'cat_tematica');
        if (!in_array('id_tematica', $cols, true) || !in_array('tematica', $cols, true)) return null;

        $id = DB::table('public.cat_tematica')
            ->whereRaw("TRIM(UPPER(tematica)) = TRIM(UPPER(?))", [$tematicaTxt])
            ->value('id_tematica');

        return !empty($id) ? (string) $id : null;
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
            if (isset($set[$c])) return $c;
        }
        return null;
    }
}