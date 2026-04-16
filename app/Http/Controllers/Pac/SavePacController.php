<?php

namespace App\Http\Controllers\Pac;

use App\Http\Controllers\Controller;
use App\Models\Pac\EntityPacModel;
use App\Support\PacVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SavePacController extends Controller
{
    private static ?bool $eventLogTableExists = null;

    public function save(Request $request)
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No autenticado.',
                ], 401);
            }

            $request->merge([
                'calificacion' => $this->normalizeDecimalInput($request->input('calificacion')),
            ]);

            $validated = $request->validate([
                'id' => ['required', 'integer'],

                // del form
                'm_horas_real'       => ['nullable', 'numeric', 'min:0'],
                'm_fecha_ini'        => ['nullable', 'date', 'before_or_equal:today'],
                'm_fecha_fin'        => ['nullable', 'date', 'after_or_equal:m_fecha_ini', 'before_or_equal:today'],
                'm_observaciones'    => ['nullable', 'string', 'max:1000'],
                'm_eval_aprendizaje' => ['nullable', 'in:0,1'],

                // selects
                'id_cat_estatus'  => ['nullable', 'integer'],
                'id_instancia'    => ['nullable', 'string', 'max:50'],
                'id_cat_tematica' => ['nullable', 'string', 'max:50'],
                'id_finalidad'    => ['nullable', 'integer'],

                // calificación
                'calificacion' => [
                    'required',
                    'numeric',
                    'between:70,100',
                    'regex:/^\d{1,3}(\.\d{1,2})?$/',
                ],
            ]);

            $id = (int) $validated['id'];

            if ($this->isAdminOrRevisorEst($user)) {
                $allowed = DB::table('public.a2_acciones_empleados')
                    ->where('id_empl_accion', $id);
            } else {
                $allowed = DB::table('public.a2_acciones_empleados')
                    ->join('public.a2_acciones_capacitacion', function ($join) {
                        $join->on(
                            DB::raw('public.a2_acciones_empleados.id_puesto::INTEGER'),
                            '=',
                            'public.a2_acciones_capacitacion.id_puesto'
                        )->whereRaw(
                            'UPPER(TRIM(public.unaccent(public.a2_acciones_empleados.curp))) = UPPER(TRIM(public.unaccent(public.a2_acciones_capacitacion.curp)))'
                        );
                    })
                    ->where('public.a2_acciones_empleados.id_empl_accion', $id);

                PacVisibility::apply($allowed, $user, 'public.a2_acciones_capacitacion');
            }

            if (! $allowed->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Acceso denegado o registro no encontrado.',
                ], 200);
            }

            $row = EntityPacModel::findOrFail($id);

            $idEstatus   = ($validated['id_cat_estatus'] ?? '') !== '' ? (int) $validated['id_cat_estatus'] : null;
            $idFinalidad = ($validated['id_finalidad'] ?? '') !== '' ? (int) $validated['id_finalidad'] : null;

            $idInstancia = trim((string) ($validated['id_instancia'] ?? ''));
            $idInstancia = $idInstancia !== '' ? $idInstancia : null;

            $idTematica = trim((string) ($validated['id_cat_tematica'] ?? ''));
            $idTematica = $idTematica !== '' ? $idTematica : null;

            $this->assertExistsInCatalog(
                'public.cat_estatus',
                'id_cat_estatus',
                $idEstatus,
                'id_cat_estatus',
                'El estatus seleccionado no existe.'
            );

            $this->assertExistsInCatalog(
                'public.cat_instancias',
                'id_instancia',
                $idInstancia,
                'id_instancia',
                'La instancia seleccionada no existe.'
            );

            $this->assertExistsInCatalog(
                'public.cat_tematica',
                'id_tematica',
                $idTematica,
                'id_cat_tematica',
                'La temática seleccionada no existe.'
            );

            $this->assertExistsInCatalog(
                'public.cat_finalidad',
                'id_finalidad',
                $idFinalidad,
                'id_finalidad',
                'La finalidad seleccionada no existe.'
            );

            $fechaIni = $validated['m_fecha_ini'] ?? null;
            $fechaFin = $validated['m_fecha_fin'] ?? null;

            $horasRealInput = $validated['m_horas_real'] ?? null;

            $idTrimestre = $row->id_trimestre;
            if (! empty($fechaIni)) {
                $m = (int) date('n', strtotime($fechaIni));
                $idTrimestre = ($m <= 3) ? 1 : (($m <= 6) ? 2 : (($m <= 9) ? 3 : 4));
            }

            $horasReal = $horasRealInput;
            if ($horasReal === null || $horasReal === '') {
                $dur = DB::table('public.a1_cat_acciones')
                    ->where('id_accion', $row->id_accion)
                    ->value('duracion_hrs');

                $horasReal = $dur !== null ? (float) $dur : null;
            } else {
                $horasReal = (float) $horasReal;
            }

            $obs = $validated['m_observaciones'] ?? null;
            $obs = $obs !== null ? mb_strtoupper(trim($obs)) : null;

            $eval = ($validated['m_eval_aprendizaje'] ?? '0') === '1' ? 1 : 0;

            $cal = round((float) $validated['calificacion'], 2);

            if ($cal < 70 || $cal > 100) {
                throw ValidationException::withMessages([
                    'calificacion' => 'La calificación debe estar entre 70 y 100.',
                ]);
            }

            if ($idEstatus !== null) {
                $errors = [];

                if (empty($fechaIni)) {
                    $errors['m_fecha_ini'] = 'La fecha de inicio es obligatoria para avanzar el registro.';
                }

                if (empty($fechaFin)) {
                    $errors['m_fecha_fin'] = 'La fecha de fin es obligatoria para avanzar el registro.';
                }

                if ($horasRealInput === null || $horasRealInput === '') {
                    $errors['m_horas_real'] = 'Las horas realizadas son obligatorias para avanzar el registro.';
                }

                if (empty($idInstancia)) {
                    $errors['id_instancia'] = 'La instancia es obligatoria para avanzar el registro.';
                }

                if (empty($idTematica)) {
                    $errors['id_cat_tematica'] = 'La temática es obligatoria para avanzar el registro.';
                }

                if ($idFinalidad === null) {
                    $errors['id_finalidad'] = 'La finalidad es obligatoria para avanzar el registro.';
                }

                if (! in_array((string) ($validated['m_eval_aprendizaje'] ?? ''), ['0', '1'], true)) {
                    $errors['m_eval_aprendizaje'] = 'Debes indicar si realizó la evaluación de aprendizaje.';
                }

                if ($cal === null || $cal === '') {
                    $errors['calificacion'] = 'La calificación es obligatoria para avanzar el registro.';
                }

                if (! empty($errors)) {
                    throw ValidationException::withMessages($errors);
                }
            }

            $row->id_cat_estatus   = $idEstatus;
            $row->id_finalidad     = $idFinalidad;
            $row->id_instancia     = $idInstancia;
            $row->id_cat_tematica  = $idTematica;
            $row->fecha_ini        = $fechaIni;
            $row->fecha_fin        = $fechaFin;
            $row->id_trimestre     = $idTrimestre;
            $row->horas_real       = $horasReal;
            $row->observaciones    = $obs;
            $row->eval_aprendizaje = $eval;
            $row->calificacion     = $cal;

            $row->save();

            $this->safeWritePacLog(
                userId: (int) $user->id,
                idEstatus: $idEstatus,
                fechaIni: $fechaIni,
                fechaFin: $fechaFin,
                idInstancia: $idInstancia,
                idTematica: $idTematica,
                idEmplAccion: (int) $row->id_empl_accion,
                horasReal: $horasReal,
                observaciones: $obs
            );

            $this->safeLogUserAction(
                userId: (int) $user->id,
                modulo: 'PAC',
                accion: 'ACTUALIZAR_REGISTRO',
                descripcion: 'Actualización de registro PAC',
                idReferencia: (string) $row->id_empl_accion,
                payload: [
                    'id_empl_accion'   => (int) $row->id_empl_accion,
                    'id_cat_estatus'   => $idEstatus,
                    'id_finalidad'     => $idFinalidad,
                    'id_instancia'     => $idInstancia,
                    'id_cat_tematica'  => $idTematica,
                    'fecha_ini'        => $fechaIni,
                    'fecha_fin'        => $fechaFin,
                    'id_trimestre'     => $idTrimestre,
                    'horas_real'       => $horasReal,
                    'eval_aprendizaje' => $eval,
                    'calificacion'     => $cal,
                    'observaciones'    => $obs,
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Registro actualizado correctamente.',
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            Log::error('Error al guardar PAC', [
                'message' => $th->getMessage(),
                'file'    => $th->getFile(),
                'line'    => $th->getLine(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => __('default.error_message'),
            ], 500);
        }
    }

    private function safeWritePacLog(
        int $userId,
        ?int $idEstatus,
        ?string $fechaIni,
        ?string $fechaFin,
        ?string $idInstancia,
        ?string $idTematica,
        int $idEmplAccion,
        ?float $horasReal,
        ?string $observaciones
    ): void {
        if (
            $idEstatus === null ||
            empty($fechaIni) ||
            empty($fechaFin) ||
            empty($idInstancia) ||
            empty($idTematica) ||
            $horasReal === null
        ) {
            return;
        }

        try {
            DB::table('log.log_info')->insert([
                'observaciones'   => mb_substr((string) ($observaciones ?: 'ACTUALIZACIÓN DE REGISTRO PAC'), 0, 280),
                'id_usuario'      => $userId,
                'id_cat_estatus'  => $idEstatus,
                'fecha_ini'       => $fechaIni,
                'fecha_fin'       => $fechaFin,
                'id_instancia'    => $idInstancia,
                'id_cat_tematica' => $idTematica,
                'creado_en'       => now(),
                'id_empl_accion'  => $idEmplAccion,
                'horas_real'      => $horasReal,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar log.log_info para PAC', [
                'message' => $e->getMessage(),
                'id_empl_accion' => $idEmplAccion,
            ]);
        }
    }

    private function safeLogUserAction(
        int $userId,
        string $modulo,
        string $accion,
        ?string $descripcion = null,
        ?string $idReferencia = null,
        ?array $payload = null
    ): void {
        try {
            if ($this->eventLogTableExists()) {
                DB::table('log.log_eventos_usuario')->insert([
                    'modulo'        => $modulo,
                    'accion'        => $accion,
                    'descripcion'   => $descripcion,
                    'id_usuario'    => $userId,
                    'id_referencia' => $idReferencia,
                    'payload'       => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                    'creado_en'     => now(),
                ]);
                return;
            }

            Log::info('AUDITORIA_USUARIO', [
                'modulo'        => $modulo,
                'accion'        => $accion,
                'descripcion'   => $descripcion,
                'id_usuario'    => $userId,
                'id_referencia' => $idReferencia,
                'payload'       => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar log_eventos_usuario', [
                'message' => $e->getMessage(),
                'modulo'  => $modulo,
                'accion'  => $accion,
            ]);
        }
    }

    private function eventLogTableExists(): bool
    {
        if (self::$eventLogTableExists !== null) {
            return self::$eventLogTableExists;
        }

        try {
            self::$eventLogTableExists = DB::table('information_schema.tables')
                ->where('table_schema', 'log')
                ->where('table_name', 'log_eventos_usuario')
                ->exists();
        } catch (\Throwable $e) {
            self::$eventLogTableExists = false;
        }

        return self::$eventLogTableExists;
    }

    private function assertExistsInCatalog(
        string $table,
        string $column,
        $value,
        string $field,
        string $message
    ): void {
        if ($value === null || $value === '') {
            return;
        }

        $exists = DB::table($table)
            ->where($column, $value)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }

    private function normalizeDecimalInput($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        $firstDot = strpos($value, '.');
        if ($firstDot !== false) {
            $before = substr($value, 0, $firstDot + 1);
            $after  = substr($value, $firstDot + 1);
            $after  = str_replace('.', '', $after);
            $value  = $before . $after;
        }

        if (str_contains($value, '.')) {
            [$entero, $decimal] = array_pad(explode('.', $value, 2), 2, '');
            $value = $entero . '.' . substr($decimal, 0, 2);
        }

        return $value;
    }

    private function isAdminOrRevisorEst($user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin_oc', 'admin', 'revisor_est'])) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            if (
                $user->hasRole('admin_oc') ||
                $user->hasRole('admin') ||
                $user->hasRole('revisor_est')
            ) {
                return true;
            }
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (isset($user->rol_id) && (int) $user->rol_id === 1) {
            return true;
        }

        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        $roleCandidates = [
            $user->role ?? null,
            $user->rol ?? null,
            $user->rol_nombre ?? null,
            $user->nombre_rol ?? null,
            $user->perfil ?? null,
        ];

        foreach ($roleCandidates as $role) {
            $role = strtolower(trim((string) $role));
            if (in_array($role, ['admin_oc', 'admin', 'revisor_est'], true)) {
                return true;
            }
        }

        return false;
    }
}