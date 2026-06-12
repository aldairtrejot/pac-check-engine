<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\Collection\CollectionFinalidadModel;
use App\Models\Pac\Collection\CollectionInstanceModel;
use App\Models\Pac\Collection\CollectionTematicaModel;
use App\Models\Pac\DataPacModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataPacController extends Controller
{
    public function dataPac(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
            ]);

            $collectionInstanceModel  = new CollectionInstanceModel;
            $collectionTematicaModel  = new CollectionTematicaModel;
            $collectionFinalidadModel = new CollectionFinalidadModel;

            $dataPacModel = new DataPacModel;

            $data = $dataPacModel->dataPac($request->id);

            if (! $data) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Registro no encontrado o sin permiso.',
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | Validación de visibilidad del curso
            |--------------------------------------------------------------------------
            | Se muestra si:
            | 1. El curso está VIGENTE en catálogo y el registro del empleado
            |    está pendiente/vigente/alta.
            | 2. El curso ya fue CONCLUIDO, aunque el catálogo ahora esté
            |    NO VIGENTE.
            |
            | Esto permite conservar historial concluido sin permitir pendientes
            | de cursos dados de baja.
            */
            $accionVisible = DB::table('public.a2_acciones_empleados as a')
                ->join('public.a1_cat_acciones as b', 'a.id_accion', '=', 'b.id_accion')
                ->where('a.id_empl_accion', (int) $request->id)
                ->where(function ($q) {
                    /*
                    |--------------------------------------------------------------------------
                    | Cursos vigentes actuales
                    |--------------------------------------------------------------------------
                    */
                    $q->where(function ($vigente) {
                        $vigente->whereRaw("TRIM(UPPER(COALESCE(b.estatus, ''))) = 'VIGENTE'")
                            ->where(function ($estadoEmpleado) {
                                $estadoEmpleado->whereNull('a.id_cat_estatus')
                                    ->orWhereIn('a.id_cat_estatus', [1, 2]);
                            });
                    })

                    /*
                    |--------------------------------------------------------------------------
                    | Cursos históricos concluidos
                    |--------------------------------------------------------------------------
                    | Aunque el catálogo esté NO VIGENTE, si el empleado ya lo concluyó,
                    | debe seguir apareciendo como historial.
                    */
                    ->orWhere(function ($historico) {
                        $historico->whereNotNull('a.id_cat_estatus')
                            ->whereNotNull('a.fecha_ini')
                            ->whereNotNull('a.fecha_fin')
                            ->whereNotNull('a.id_trimestre')
                            ->whereNotNull('a.id_instancia')
                            ->whereRaw("TRIM(a.id_instancia) <> ''")
                            ->whereNotNull('a.id_cat_tematica')
                            ->whereRaw("TRIM(a.id_cat_tematica) <> ''");
                    });
                })
                ->exists();

            if (! $accionVisible) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Este curso ya no está vigente y no puede mostrarse.',
                ], 200);
            }

            if (empty($data->horas_real)) {
                $data->horas_real = $data->duracion_hrs;
            }

            /*
            |--------------------------------------------------------------------------
            | Total de horas reales
            |--------------------------------------------------------------------------
            | Suma:
            | - Cursos vigentes actuales.
            | - Cursos históricos concluidos, aunque el catálogo ya esté NO VIGENTE.
            |
            | No suma pendientes de cursos dados de baja / no vigentes.
            */
            $totalHorasReal = DB::table('public.a2_acciones_empleados as a')
                ->join('public.a1_cat_acciones as b', 'a.id_accion', '=', 'b.id_accion')
                ->whereRaw('UPPER(TRIM(a.curp)) = UPPER(TRIM(?))', [$data->curp])
                ->where(function ($q) {
                    /*
                    |--------------------------------------------------------------------------
                    | Cursos vigentes actuales
                    |--------------------------------------------------------------------------
                    */
                    $q->where(function ($vigente) {
                        $vigente->whereRaw("TRIM(UPPER(COALESCE(b.estatus, ''))) = 'VIGENTE'")
                            ->where(function ($estadoEmpleado) {
                                $estadoEmpleado->whereNull('a.id_cat_estatus')
                                    ->orWhereIn('a.id_cat_estatus', [1, 2]);
                            });
                    })

                    /*
                    |--------------------------------------------------------------------------
                    | Cursos históricos concluidos
                    |--------------------------------------------------------------------------
                    */
                    ->orWhere(function ($historico) {
                        $historico->whereNotNull('a.id_cat_estatus')
                            ->whereNotNull('a.fecha_ini')
                            ->whereNotNull('a.fecha_fin')
                            ->whereNotNull('a.id_trimestre')
                            ->whereNotNull('a.id_instancia')
                            ->whereRaw("TRIM(a.id_instancia) <> ''")
                            ->whereNotNull('a.id_cat_tematica')
                            ->whereRaw("TRIM(a.id_cat_tematica) <> ''");
                    });
                })
                ->sum('a.horas_real');

            $listOptionStatus = DB::table('public.cat_estatus')
                ->select('id_cat_estatus as id', 'descripcion')
                ->whereIn('id_cat_estatus', [1, 2, 3])
                ->orderBy('id_cat_estatus')
                ->get();

            if (isset($data->id_cat_estatus) && in_array((int) $data->id_cat_estatus, [1, 2, 3], true)) {
                $listSelectStatus = $listOptionStatus
                    ->where('id', (int) $data->id_cat_estatus)
                    ->values()
                    ->all();
            } else {
                $listSelectStatus = [];
            }

            $listOptionInstance = $collectionInstanceModel->listCollection();

            $listSelectInstance = isset($data->id_instancia)
                ? $collectionInstanceModel->listConllectionSelect($data->id_instancia)
                : [];

            $listOptionTematica = $collectionTematicaModel->listCollection();

            if (! empty($data->id_cat_tematica)) {
                $listSelectTematica = $collectionTematicaModel->listConllectionSelect($data->id_cat_tematica);
            } else {
                $listSelectTematica = [];

                if (! empty($data->tematica_accion)) {
                    $row = DB::table('public.cat_tematica')
                        ->select(
                            'public.cat_tematica.id_tematica as id',
                            'public.cat_tematica.tematica as descripcion'
                        )
                        ->whereRaw(
                            'TRIM(UPPER(public.cat_tematica.tematica)) = TRIM(UPPER(?))',
                            [$data->tematica_accion]
                        )
                        ->first();

                    if ($row) {
                        $listSelectTematica = [$row];
                    }
                }
            }

            $listOptionFinalidad = $collectionFinalidadModel->listCollection();

            if (! empty($data->id_finalidad)) {
                $listSelectFinalidad = $collectionFinalidadModel->listConllectionSelect($data->id_finalidad);
            } else {
                $listSelectFinalidad = $collectionFinalidadModel->listConllectionSelect(6);
            }

            return response()->json([
                'status'              => true,
                'data'                => $data,
                'totalHorasReal'      => (float) $totalHorasReal,
                'listOptionStatus'    => $listOptionStatus,
                'listSelectStatus'    => $listSelectStatus,
                'listOptionInstance'  => $listOptionInstance,
                'listSelectInstance'  => $listSelectInstance,
                'listOptionTematica'  => $listOptionTematica,
                'listSelectTematica'  => $listSelectTematica,
                'listOptionFinalidad' => $listOptionFinalidad,
                'listSelectFinalidad' => $listSelectFinalidad,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Error en DataPacController@dataPac', [
                'message' => $th->getMessage(),
                'file'    => $th->getFile(),
                'line'    => $th->getLine(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'No se pudo cargar la información del registro.',
            ], 200);
        }
    }
}