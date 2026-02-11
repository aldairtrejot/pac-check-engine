<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoursePacController extends Controller
{
    /**
     * POST /pac/courses
     * Lista cursos disponibles (SOLO ADMIN/CENTRAL)
     */
    public function listCourses(Request $request)
    {
        $user = auth()->user();

        abort_unless(PacVisibility::canAddCourse($user), 403, 'Solo el administrador puede agregar cursos.');

        $list = DB::table('public.a1_cat_acciones')
            ->selectRaw("id_accion as id, nombre_accion as text")
            ->orderBy('nombre_accion', 'asc')
            ->get();

        return response()->json([
            'status'      => true,
            'listCourses' => $list,
        ], 200);
    }

    /**
     * POST /pac/employee/add-course
     * Agrega un curso al empleado (SOLO ADMIN/CENTRAL)
     */
    public function addCourseToEmployee(Request $request)
    {
        $user = auth()->user();

        abort_unless(PacVisibility::canAddCourse($user), 403, 'Solo el administrador puede agregar cursos.');

        $validated = $request->validate([
            'id_empl_accion_base' => 'required|integer',
            'id_accion'           => 'required|integer',
        ]);

        try {
            return DB::transaction(function () use ($validated) {

                $base = DB::table('public.a2_acciones_empleados')
                    ->where('id_empl_accion', (int) $validated['id_empl_accion_base'])
                    ->first();

                if (! $base) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Registro base no encontrado.',
                    ], 200);
                }

                // ✅ Validar que exista el curso en catálogo
                $existsCourse = DB::table('public.a1_cat_acciones')
                    ->where('id_accion', (int) $validated['id_accion'])
                    ->exists();

                if (! $existsCourse) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'El curso seleccionado no existe en el catálogo.',
                    ], 200);
                }

                // ✅ Evitar duplicados (mismo empleado/puesto/curso)
                $already = DB::table('public.a2_acciones_empleados')
                    ->where('curp', $base->curp)
                    ->where(DB::raw('id_puesto::INTEGER'), (int) $base->id_puesto)
                    ->where('id_accion', (int) $validated['id_accion'])
                    ->exists();

                if ($already) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Este empleado ya tiene asignado ese curso.',
                    ], 200);
                }

                // ✅ Copiar estructura base para cumplir NOT NULL (si existen)
                $cols = DB::table('information_schema.columns')
                    ->where('table_schema', 'public')
                    ->where('table_name', 'a2_acciones_empleados')
                    ->orderBy('ordinal_position')
                    ->pluck('column_name')
                    ->map(fn ($c) => (string) $c)
                    ->all();

                $insert = [];
                foreach ($cols as $col) {
                    if ($col === 'id_empl_accion') continue; // PK autoincremental

                    // copiamos valores del base si existen
                    if (property_exists($base, $col)) {
                        $insert[$col] = $base->$col;
                    }
                }

                // ✅ Override para nuevo curso (en estado "pendiente")
                $insert['id_accion'] = (int) $validated['id_accion'];

                // reseteo de campos típicos del “atendido”
                if (array_key_exists('id_cat_estatus', $insert))  $insert['id_cat_estatus'] = null;
                if (array_key_exists('id_instancia', $insert))    $insert['id_instancia'] = null;
                if (array_key_exists('id_cat_tematica', $insert)) $insert['id_cat_tematica'] = null;
                if (array_key_exists('fecha_ini', $insert))       $insert['fecha_ini'] = null;
                if (array_key_exists('fecha_fin', $insert))       $insert['fecha_fin'] = null;
                if (array_key_exists('id_trimestre', $insert))    $insert['id_trimestre'] = null;
                if (array_key_exists('horas_real', $insert))      $insert['horas_real'] = null;
                if (array_key_exists('observaciones', $insert))   $insert['observaciones'] = null;
                if (array_key_exists('eval_aprendizaje', $insert))$insert['eval_aprendizaje'] = 0;

                // defaults de tu requerimiento
                if (array_key_exists('calificacion', $insert))    $insert['calificacion'] = 100;

                // si quieres que herede finalidad del base, deja esto:
                if (array_key_exists('id_finalidad', $insert))    $insert['id_finalidad'] = $base->id_finalidad ?? null;

                // timestamps si existen (por si tu tabla los maneja)
                if (in_array('created_at', $cols, true)) $insert['created_at'] = now();
                if (in_array('updated_at', $cols, true)) $insert['updated_at'] = now();

                $newId = DB::table('public.a2_acciones_empleados')->insertGetId(
                    $insert,
                    'id_empl_accion'
                );

                return response()->json([
                    'status'  => true,
                    'message' => 'Curso agregado correctamente.',
                    'id'      => $newId,
                ], 200);
            });

        } catch (\Throwable $e) {
            Log::error('Error addCourseToEmployee', ['e' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'No se pudo agregar el curso. Revisa logs.',
            ], 200);
        }
    }
}