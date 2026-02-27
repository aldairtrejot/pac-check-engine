<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateEstatusConstanciasController extends Controller
{
    /**
     * ACEPTAR: procesa y carga en Mi plantilla (a2_acciones_empleados)
     * RECHAZAR: por ahora NO toca mi plantilla (se puede definir luego)
     */

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
                return response()->json([
                    'status'  => false,
                    'message' => 'Acción inválida.',
                ], 200);
            }

            if ($accion === 'RECHAZAR') {
                // ✅ aquí luego definimos si se guarda motivo o si impacta algo más
                return response()->json([
                    'status'  => true,
                    'message' => 'Registro rechazado (pendiente definir impacto en Mi plantilla).',
                ], 200);
            }

            // ✅ ACEPTAR
            return DB::transaction(function () use ($idRespuesta) {

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
                        'status'  => false,
                        'message' => 'Registro de constancia no encontrado.',
                    ], 200);
                }

                $curp       = trim((string) ($c->curp ?? ''));
                $nombreCurso = trim((string) ($c->nombre_curso ?? ''));
                $idPuesto   = trim((string) ($c->id_puesto ?? ''));

                if ($curp === '' || $nombreCurso === '') {
                    return response()->json([
                        'status'  => false,
                        'message' => 'El registro no tiene CURP o nombre de curso.',
                    ], 200);
                }

                // 2) Validar que el curso exista en catálogo (a1_cat_acciones)
                $accionCat = DB::table('public.a1_cat_acciones')
                    ->select(['id_accion', 'duracion_hrs', 'nombre_accion'])
                    ->whereRaw('TRIM(UPPER(nombre_accion)) = TRIM(UPPER(?))', [$nombreCurso])
                    ->first();

                if (!$accionCat) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'El curso NO existe en el catálogo. No se puede procesar.',
                    ], 200);
                }

                $idAccion = (int) $accionCat->id_accion;
                $horasProgramadas = $accionCat->duracion_hrs ?? null;

                // 3) Resolver id_cat_estatus de "ATENDIDO" (Mi plantilla)
                $idCatAtendido = DB::table('public.cat_estatus')
                    ->whereRaw("TRIM(UPPER(descripcion)) = 'ATENDIDO'")
                    ->value('id_cat_estatus');

                if (!$idCatAtendido) {
                    return response()->json([
                        'status'  => false,
                        'message' => "No existe el estatus 'ATENDIDO' en cat_estatus. Créalo en BD y reintenta.",
                    ], 200);
                }

                // 4) Verificar duplicado (misma lógica que tu PAC):
                //    mismo id_puesto + curp + id_accion
                $existe = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuesto])
                    ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                    ->where('id_accion', $idAccion)
                    ->exists();

                // 5) Parse horas_realizadas (varchar -> float)
                $horasReal = null;
                $hrsRaw = trim((string) ($c->horas_realizadas ?? ''));
                if ($hrsRaw !== '') {
                    $hrsRaw = str_replace(',', '.', $hrsRaw);
                    $n = floatval($hrsRaw);
                    $horasReal = is_numeric($n) ? $n : null;
                }

                // 6) Calificación (si viene)
                $cal = 100;
                $calRaw = trim((string) ($c->calificacion ?? ''));
                if ($calRaw !== '' && is_numeric($calRaw)) {
                    $cal = (int) $calRaw;
                }
                if ($cal < 70) $cal = 70;
                if ($cal > 100) $cal = 100;

                // 7) Trimestre por fecha_inicio
                $idTrimestre = null;
                if (!empty($c->fecha_inicio)) {
                    try {
                        $m = (int) date('n', strtotime($c->fecha_inicio));
                        $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
                    } catch (\Throwable $e) {
                        $idTrimestre = null;
                    }
                }

                // 8) Si existe, NO insertar: solo actualizar datos + ATENDIDO
                if ($existe) {
                    DB::table('public.a2_acciones_empleados')
                        ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuesto])
                        ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                        ->where('id_accion', $idAccion)
                        ->update([
                            'id_cat_estatus'   => $idCatAtendido,
                            'id_finalidad'     => 6,
                            'horas_real'       => $horasReal,
                            'fecha_ini'        => $c->fecha_inicio ?? null,
                            'fecha_fin'        => $c->fecha_final ?? null,
                            'id_trimestre'     => $idTrimestre,
                            'horas_progamadas' => DB::raw('COALESCE(horas_progamadas, '.($horasProgramadas === null ? 'NULL' : $horasProgramadas).')'),
                            // si tu columna calificacion existe (en tu sistema sí), se actualiza:
                            'calificacion'     => $cal,
                            'observaciones'    => DB::raw("COALESCE(observaciones, 'Cargado desde Constancias (id_respuesta: {$c->id_respuesta})')"),
                        ]);

                    return response()->json([
                        'status'  => true,
                        'message' => 'Procesado correctamente. El curso ya existía; se actualizó el empleado a ATENDIDO.',
                    ], 200);
                }

                // 9) Si NO existe: insertar NUEVO registro (misma lógica de tu addCourseToEmployee)

                // 🔒 Locks para evitar colisiones en max+1
                DB::select("SELECT pg_advisory_xact_lock(3001)");
                DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$curp]);

                $maxId = DB::table('public.a2_acciones_empleados')->max('id_empl_accion');
                $newId = $maxId ? ((int)$maxId + 1) : 1;

                $maxNumCurso = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuesto])
                    ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                    ->max('id_num_curso');

                $nextNumCurso = $maxNumCurso ? ((int)$maxNumCurso + 1) : 1;

                // ✅ Buscar un "base" si existe, para copiar (misma filosofía que tú)
                $base = DB::table('public.a2_acciones_empleados')
                    ->whereRaw('TRIM(id_puesto) = TRIM(?)', [$idPuesto])
                    ->whereRaw('TRIM(UPPER(curp)) = TRIM(UPPER(?))', [$curp])
                    ->orderBy('id_empl_accion', 'ASC')
                    ->first();

                $payload = $base ? (array) $base : [];

                // ✅ forzar campos clave
                $payload['id_empl_accion']   = $newId;
                $payload['id_puesto']        = $idPuesto !== '' ? $idPuesto : ($payload['id_puesto'] ?? null);
                $payload['curp']             = $curp;
                $payload['id_accion']        = $idAccion;
                $payload['id_finalidad']     = 6;
                $payload['id_num_curso']     = $nextNumCurso;

                // datos desde constancia
                $payload['horas_real']       = $horasReal;
                $payload['fecha_ini']        = $c->fecha_inicio ?? null;
                $payload['fecha_fin']        = $c->fecha_final ?? null;
                $payload['id_trimestre']     = $idTrimestre;

                // catálogo
                $payload['horas_progamadas'] = $horasProgramadas;
                $payload['id_cat_estatus']   = $idCatAtendido;

                // si existe columna en tu tabla (en tu sistema sí)
                $payload['calificacion']     = $cal;

                // limpia/normaliza campos que no queremos copiar “sucios”
                $payload['id_instancia']     = $payload['id_instancia'] ?? null;
                $payload['costo_unitario']   = $payload['costo_unitario'] ?? null;
                $payload['eval_aprendizaje'] = $payload['eval_aprendizaje'] ?? null;
                $payload['id_cat_tematica']  = $payload['id_cat_tematica'] ?? null;

                $payload['observaciones']    = 'Cargado desde Constancias (id_respuesta: '.$c->id_respuesta.')';

                unset($payload['created_at'], $payload['updated_at']);

                DB::table('public.a2_acciones_empleados')->insert($payload);

                return response()->json([
                    'status'  => true,
                    'message' => 'Procesado correctamente. Se agregó el empleado a Mi plantilla como ATENDIDO.',
                ], 200);
            });

        } catch (\Throwable $th) {
            Log::error('Constancias update estatus error: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Ocurrió un error al procesar el registro.',
            ], 200);
        }
    }
}