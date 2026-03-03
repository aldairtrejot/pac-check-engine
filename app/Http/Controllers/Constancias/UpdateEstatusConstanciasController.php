<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEstatusConstanciasController extends Controller
{
    // cat_constancia_estatus (ajusta si tu catálogo maneja otros IDs)
    private const CONST_PENDIENTE = 1;
    private const CONST_CONCLUIDO = 2;
    private const CONST_RECHAZADO = 3; // si no existe en tu catálogo, cámbialo o quítalo

    // cat_estatus (plantilla) -> solo para que no sea NULL
    // tu lista: 1=VIGENTE, 2=ALTA, 3=BAJA, 4=NO VIGENTE
    private const PLANTILLA_ID_CAT_ESTATUS_DEFAULT = 1;

    public function update(Request $request)
    {
        try {
            $request->validate([
                'id_respuesta' => 'required',
                'accion'       => 'required|string',
            ]);

            $idRespuesta = trim((string) $request->id_respuesta);
            $accion      = strtoupper(trim((string) $request->accion)); // ACEPTAR | RECHAZAR

            if (!in_array($accion, ['ACEPTAR', 'RECHAZAR'], true)) {
                return response()->json(['status' => false, 'message' => 'Acción inválida.'], 200);
            }

            // ✅ RECHAZAR: solo cambia estatus en constancias
            if ($accion === 'RECHAZAR') {
                $updated = DB::table('public.tbl_constancias')
                    ->where('id_respuesta', $idRespuesta)
                    ->update([
                        'estatus' => self::CONST_RECHAZADO,
                        'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_DATE)"),
                        'fecha_ultima_accion' => DB::raw("CURRENT_DATE"),
                    ]);

                return response()->json([
                    'status'  => (bool) $updated,
                    'message' => $updated ? 'Constancia rechazada.' : 'Registro no encontrado.',
                ], 200);
            }

            // ✅ ACEPTAR
            return DB::transaction(function () use ($idRespuesta) {

                // === columnas reales de a2_acciones_empleados (para no insertar columnas que no existen) ===
                $colsEmp = $this->columnsFor('public', 'a2_acciones_empleados');
                $colSet  = array_flip($colsEmp);

                $hasCalificacion = isset($colSet['calificacion']);         // ✅ tu BD actual: NO existe
                $hasHorasProg    = isset($colSet['horas_progamadas']);     // ✅ sí existe en tu DDL
                $hasEvalApr      = isset($colSet['eval_aprendizaje']);     // ✅ sí existe

                // 1) Traer constancia
                $c = DB::table('public.tbl_constancias as c')
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
                    ])
                    ->where('c.id_respuesta', $idRespuesta)
                    ->first();

                if (!$c) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Registro de constancia no encontrado.',
                    ], 200);
                }

                $curp        = trim((string) ($c->curp ?? ''));
                $cursoTxt    = trim((string) ($c->nombre_curso ?? ''));
                $idPuestoTxt = trim((string) ($c->id_puesto ?? ''));

                if ($curp === '' || $cursoTxt === '' || $idPuestoTxt === '') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Faltan datos en la constancia (CURP, curso o id_puesto).',
                    ], 200);
                }

                // 2) Validar curso exista en catálogo (a1_cat_acciones)
                $accionCat = DB::table('public.a1_cat_acciones')
                    ->select(['id_accion', 'duracion_hrs'])
                    ->whereRaw('TRIM(UPPER(nombre_accion)) = TRIM(UPPER(?))', [$cursoTxt])
                    ->first();

                if (!$accionCat) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'El curso NO existe en el catálogo (a1_cat_acciones). No se puede procesar.',
                    ], 200);
                }

                $idAccion = (int) $accionCat->id_accion;
                $horasProgramadas = $accionCat->duracion_hrs ?? null;

                // 3) Instancia -> id_instancia
                $instTxt = trim((string) (($c->instancia ?? '') !== '' ? $c->instancia : ($c->instancia_otro ?? '')));
                $idInstancia = $this->resolveIdInstanciaSafe($instTxt);

                if (!$idInstancia) {
                    return response()->json([
                        'status'  => false,
                        'message' => "No se pudo mapear la instancia '{$instTxt}' a un id_instancia del catálogo.",
                    ], 200);
                }

                // 4) Temática: a1_cat_acciones.tematica -> cat_tematica.id_tematica
                $tematicaTxt = $this->resolveTematicaFromCursoSafe($idAccion);
                if (!$tematicaTxt) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'No se pudo obtener la temática asociada al curso desde el catálogo.',
                    ], 200);
                }

                $idTematica = $this->resolveIdTematicaSafe($tematicaTxt);
                if (!$idTematica) {
                    return response()->json([
                        'status'  => false,
                        'message' => "No se pudo mapear la temática '{$tematicaTxt}' a cat_tematica.",
                    ], 200);
                }

                // 5) Trimestre por fecha_inicio
                $idTrimestre = null;
                if (!empty($c->fecha_inicio)) {
                    $m = (int) date('n', strtotime($c->fecha_inicio));
                    $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
                }

                if (empty($c->fecha_inicio) || empty($c->fecha_final) || empty($idTrimestre)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'La constancia no trae fechas suficientes (fecha_inicio/fecha_final).',
                    ], 200);
                }

                // 6) horas_realizadas (varchar -> float)
                $horasReal = null;
                $hrsRaw = trim((string) ($c->horas_realizadas ?? ''));
                if ($hrsRaw !== '') {
                    $hrsRaw = str_replace(',', '.', $hrsRaw);
                    $horasReal = (float) $hrsRaw;
                }

                // 7) calificación clamp 70..100 (solo si existe columna, si no: va a observaciones)
                $cal = 100;
                $calRaw = trim((string) ($c->calificacion ?? ''));
                if ($calRaw !== '' && is_numeric($calRaw)) $cal = (int) $calRaw;
                if ($cal < 70) $cal = 70;
                if ($cal > 100) $cal = 100;

                // 8) Duplicado: mismo id_puesto + curp + id_accion
                $existe = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                    ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                    ->where('id_accion', $idAccion)
                    ->exists();

                // lock para max+1
                DB::select("SELECT pg_advisory_xact_lock(5001)");
                DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$curp]);

                // Observación base (y metemos calificación aquí si NO existe columna)
                $obsBase = 'Cargado desde Constancias (id_respuesta: '.$c->id_respuesta.').';
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
                        'observaciones'    => DB::raw("COALESCE(observaciones, '{$obsBase}')"),
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
                    // Insert NUEVO
                    $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                    $newId = $maxId ? ((int)$maxId + 1) : 1;

                    $maxNumCurso = DB::table('public.a2_acciones_empleados')
                        ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuestoTxt])
                        ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                        ->max('id_num_curso');

                    $nextNumCurso = $maxNumCurso ? ((int)$maxNumCurso + 1) : 1;

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

                // 9) Cambiar estatus en constancias (cat_constancia_estatus)
                DB::table('public.tbl_constancias')
                    ->where('id_respuesta', $idRespuesta)
                    ->update([
                        'estatus' => self::CONST_CONCLUIDO,
                        'fecha_ini_accion'    => DB::raw("COALESCE(fecha_ini_accion, CURRENT_DATE)"),
                        'fecha_ultima_accion' => DB::raw("CURRENT_DATE"),
                    ]);

                return response()->json([
                    'status'  => true,
                    'message' => 'Constancia aceptada y procesada correctamente.',
                ], 200);
            });

        } catch (\Throwable $th) {
            Log::error('Constancias ACEPTAR error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al procesar el registro.',
            ], 200);
        }
    }

    /**
     * ✅ Resolve seguro de id_instancia usando information_schema.
     */
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

    /**
     * ✅ Temática del curso desde a1_cat_acciones.tematica (si existe columna).
     */
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

    /**
     * ✅ Mapea temática a id_tematica.
     */
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